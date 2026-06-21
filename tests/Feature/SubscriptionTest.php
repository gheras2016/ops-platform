<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use DatabaseTransactions;

    protected SubscriptionService $svc;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(SubscriptionService::class);
        $this->plan = Plan::create([
            'name' => 'Yearly', 'slug' => 'yearly-' . uniqid(),
            'price' => 1999, 'currency' => 'SAR',
            'billing_period' => Plan::PERIOD_YEARLY, 'duration_days' => 365,
        ]);
    }

    protected function company(): Company
    {
        return Company::create(['name' => 'Sub Co', 'code' => 'SC' . rand(1000, 9999)]);
    }

    protected function admin(Company $company): User
    {
        $u = User::create([
            'company_id' => $company->id, 'name' => 'Admin',
            'email' => uniqid() . '@sub.local', 'password' => bcrypt('secret123'), 'is_active' => true,
        ]);
        $u->assignRole(User::ROLE_COMPANY_ADMIN);

        return $u;
    }

    public function test_new_company_starts_on_a_trial(): void
    {
        $c = $this->company();

        $this->assertEquals(Company::SUB_TRIAL, $c->subscription_status);
        $this->assertNotNull($c->trial_ends_at);
        $this->assertTrue($c->is_active);
        $this->assertGreaterThanOrEqual(6, $c->daysRemaining());
    }

    public function test_subscribe_activates_and_records_payment(): void
    {
        $c = $this->company();

        $this->svc->subscribe($c, $this->plan, ['gateway' => 'moyasar', 'reference' => 'tx_1']);
        $c->refresh();

        $this->assertEquals(Company::SUB_ACTIVE, $c->subscription_status);
        $this->assertEqualsWithDelta(365, $c->daysRemaining(), 1);
        $this->assertDatabaseHas('subscription_payments', [
            'company_id' => $c->id, 'status' => 'paid', 'gateway' => 'moyasar', 'reference' => 'tx_1',
        ]);
    }

    public function test_renewal_stacks_onto_unexpired_period(): void
    {
        $c = $this->company();
        $this->svc->subscribe($c, $this->plan);
        $this->svc->subscribe($c->refresh(), $this->plan);

        $this->assertEqualsWithDelta(730, $c->refresh()->daysRemaining(), 2);
    }

    public function test_expiry_moves_to_grace_then_suspends(): void
    {
        $c = $this->company();
        $c->update(['subscription_status' => Company::SUB_ACTIVE, 'trial_ends_at' => null,
            'current_period_end' => now()->subDay(), 'grace_days' => 3]);

        // Just expired → grace, still usable.
        $this->assertEquals(Company::SUB_GRACE, $this->svc->processExpiry($c));
        $this->assertTrue($c->refresh()->is_active);

        // Past the grace window → suspended + blocked.
        $c->update(['current_period_end' => now()->subDays(5)]);
        $this->assertEquals(Company::SUB_SUSPENDED, $this->svc->processExpiry($c));
        $this->assertFalse($c->refresh()->is_active);
    }

    public function test_grandfathered_company_without_deadline_never_expires(): void
    {
        $c = $this->company();
        $c->update(['subscription_status' => Company::SUB_ACTIVE, 'trial_ends_at' => null, 'current_period_end' => null]);

        $this->assertNull($this->svc->processExpiry($c));
        $this->assertNull($c->daysRemaining());
    }

    public function test_suspended_company_cannot_login(): void
    {
        $c = $this->company();
        $admin = $this->admin($c);
        $this->svc->suspend($c);

        $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email, 'password' => 'secret123', 'device_name' => 'x',
        ])->assertStatus(422);
    }

    public function test_tick_reminds_then_suspends_and_notifies_admins(): void
    {
        // (a) reminder at 7 days for a trial.
        $c = $this->company();
        $admin = $this->admin($c);
        $c->update(['subscription_status' => Company::SUB_TRIAL, 'trial_ends_at' => now()->addDays(7)]);

        Artisan::call('subscriptions:tick');
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $admin->id]);

        // (b) suspension past grace.
        $c->update(['subscription_status' => Company::SUB_ACTIVE, 'trial_ends_at' => null,
            'current_period_end' => now()->subDays(10), 'grace_days' => 3]);
        Artisan::call('subscriptions:tick');

        $this->assertEquals(Company::SUB_SUSPENDED, $c->refresh()->subscription_status);
        $this->assertFalse($c->is_active);
    }
}
