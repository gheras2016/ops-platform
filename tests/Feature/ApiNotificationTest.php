<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiNotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::create(['name' => 'N Co', 'code' => 'N' . rand(1000, 9999)]);
        $dept = Department::create(['company_id' => $company->id, 'name' => 'Maint', 'type' => 'maintenance']);
        $this->user = User::create([
            'company_id' => $company->id,
            'department_id' => $dept->id,
            'name' => 'Tech',
            'email' => uniqid() . '@n.local',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);
        $this->user->assignRole(User::ROLE_TECHNICIAN);

        $this->ticket = Ticket::create([
            'company_id' => $company->id,
            'department_id' => $dept->id,
            'created_by' => $this->user->id,
            'ticket_number' => 'TKT-N-' . rand(1000, 9999),
            'title' => 'مضخة معطّلة',
            'status' => Ticket::STATUS_ASSIGNED,
        ]);
    }

    protected function notify(): void
    {
        $this->user->notify(new TicketNotification($this->ticket, 'assigned', 'تم إسناد البلاغ إليك'));
    }

    public function test_index_returns_flattened_notifications(): void
    {
        $this->notify();

        Sanctum::actingAs($this->user);
        $res = $this->getJson('/api/v1/notifications')->assertOk();

        $first = $res->json('data.0');
        $this->assertEquals('assigned', $first['event']);
        $this->assertEquals('تم إسناد البلاغ إليك', $first['message']);
        $this->assertEquals($this->ticket->id, $first['ticket_id']);
        $this->assertFalse($first['is_read']);
    }

    public function test_unread_count(): void
    {
        $this->notify();
        $this->notify();

        Sanctum::actingAs($this->user);
        $this->getJson('/api/v1/notifications/unread-count')
            ->assertOk()->assertJson(['count' => 2]);
    }

    public function test_mark_one_read(): void
    {
        $this->notify();
        $id = $this->user->notifications()->first()->id;

        Sanctum::actingAs($this->user);
        $this->postJson("/api/v1/notifications/{$id}/read")
            ->assertOk()->assertJsonPath('data.is_read', true);

        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }

    public function test_mark_all_read(): void
    {
        $this->notify();
        $this->notify();

        Sanctum::actingAs($this->user);
        $this->postJson('/api/v1/notifications/read-all')->assertOk()->assertJson(['count' => 0]);

        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }

    public function test_unread_filter(): void
    {
        $this->notify();
        $this->user->notifications()->first()->markAsRead();
        $this->notify(); // one unread remains

        Sanctum::actingAs($this->user);
        $res = $this->getJson('/api/v1/notifications?unread=1')->assertOk();

        $this->assertCount(1, $res->json('data'));
    }

    public function test_cannot_read_another_users_notification(): void
    {
        $this->notify();
        $id = $this->user->notifications()->first()->id;

        $other = User::create([
            'company_id' => $this->user->company_id,
            'name' => 'Other',
            'email' => uniqid() . '@n.local',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        Sanctum::actingAs($other);
        $this->postJson("/api/v1/notifications/{$id}/read")->assertNotFound();
    }
}
