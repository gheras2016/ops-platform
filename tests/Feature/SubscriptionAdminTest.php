<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SubscriptionAdminTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected Company $company;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'company_id' => null, 'name' => 'Super', 'email' => uniqid() . '@ops.test',
            'password' => bcrypt('secret123'), 'is_active' => true,
        ]);
        $this->superAdmin->assignRole(User::ROLE_SUPER_ADMIN);

        $this->company = Company::create(['name' => 'Acme', 'code' => 'AC' . rand(1000, 9999)]);
        $this->plan = Plan::create([
            'name' => 'Yearly', 'slug' => 'yearly-' . uniqid(), 'price' => 1999,
            'billing_period' => Plan::PERIOD_YEARLY, 'duration_days' => 365,
        ]);
    }

    public function test_super_admin_can_view_subscriptions_dashboard(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('subscriptions.index'))
            ->assertOk()
            ->assertSee('الاشتراكات');
    }

    public function test_non_super_admin_is_forbidden(): void
    {
        $admin = User::create([
            'company_id' => $this->company->id, 'name' => 'A', 'email' => uniqid() . '@x.test',
            'password' => bcrypt('secret123'), 'is_active' => true,
        ]);
        $admin->assignRole(User::ROLE_COMPANY_ADMIN);

        $this->actingAs($admin)->get(route('subscriptions.index'))->assertForbidden();
    }

    public function test_activate_subscribes_the_company(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('subscriptions.activate', $this->company), ['plan_id' => $this->plan->id])
            ->assertRedirect();

        $this->company->refresh();
        $this->assertEquals(Company::SUB_ACTIVE, $this->company->subscription_status);
        $this->assertDatabaseHas('subscription_payments', ['company_id' => $this->company->id, 'status' => 'paid']);
    }

    public function test_extend_trial_and_suspend(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('subscriptions.extend', $this->company), ['days' => 14])
            ->assertRedirect();
        $this->assertEquals(Company::SUB_TRIAL, $this->company->refresh()->subscription_status);
        $this->assertGreaterThanOrEqual(13, $this->company->daysRemaining());

        $this->actingAs($this->superAdmin)
            ->post(route('subscriptions.suspend', $this->company))
            ->assertRedirect();
        $this->assertEquals(Company::SUB_SUSPENDED, $this->company->refresh()->subscription_status);
        $this->assertFalse($this->company->is_active);
    }
}
