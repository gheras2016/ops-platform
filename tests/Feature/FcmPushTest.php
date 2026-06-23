<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DeviceToken;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketNotification;
use App\Services\FcmSender;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FcmPushTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        // A throwaway RSA key (fixture) so the JWT can be signed in tests.
        $pem = file_get_contents(base_path('tests/fixtures/test_rsa_key.pem'));

        config()->set('services.fcm.credentials', json_encode([
            'project_id' => 'ops-test',
            'client_email' => 'svc@ops-test.iam.gserviceaccount.com',
            'private_key' => $pem,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ]));
    }

    protected function user(): User
    {
        $company = Company::create(['name' => 'F', 'code' => 'F' . rand(1000, 9999)]);
        $u = User::create([
            'company_id' => $company->id, 'name' => 'U', 'email' => uniqid() . '@f.local',
            'password' => bcrypt('secret123'), 'is_active' => true,
        ]);
        $u->assignRole(User::ROLE_TECHNICIAN);

        return $u;
    }

    public function test_sends_push_to_the_fcm_v1_endpoint(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'ya29.test', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/ops-test/messages/1']),
        ]);

        app(FcmSender::class)->send(['device_tok_1'], 'عنوان', 'نص', ['ticket_id' => 5]);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'fcm.googleapis.com')
            && $r->hasHeader('Authorization', 'Bearer ya29.test'));
    }

    public function test_prunes_an_unregistered_token(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 't']),
            'fcm.googleapis.com/*' => Http::response(['error' => ['status' => 'UNREGISTERED']], 404),
        ]);
        $user = $this->user();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'dead_tok', 'platform' => 'android']);

        app(FcmSender::class)->send(['dead_tok'], 't', 'b');

        $this->assertDatabaseMissing('device_tokens', ['token' => 'dead_tok']);
    }

    public function test_noop_without_credentials(): void
    {
        config()->set('services.fcm.credentials', null);
        Http::fake();

        app(FcmSender::class)->send(['x'], 't', 'b');

        Http::assertNothingSent();
    }

    public function test_ticket_notification_pushes_to_registered_device(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'tok']),
            'fcm.googleapis.com/*' => Http::response(['name' => 'ok']),
        ]);

        $user = $this->user();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'live_tok', 'platform' => 'android']);
        $ticket = Ticket::create([
            'company_id' => $user->company_id, 'ticket_number' => 'TKT-' . uniqid(),
            'title' => 'X', 'status' => Ticket::STATUS_OPEN,
        ]);

        $user->notify(new TicketNotification($ticket, 'assigned', 'تم إسناد البلاغ إليك'));

        Http::assertSent(fn ($r) => str_contains($r->url(), 'fcm.googleapis.com'));
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->id]); // database channel still works
    }
}
