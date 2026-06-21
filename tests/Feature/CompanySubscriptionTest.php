<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CompanySubscriptionTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected User $admin;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Acme', 'code' => 'AC' . rand(1000, 9999)]);
        $this->admin = $this->user(User::ROLE_COMPANY_ADMIN);
        $this->plan = Plan::create([
            'name' => 'Yearly', 'slug' => 'y-' . uniqid(), 'price' => 1999,
            'billing_period' => Plan::PERIOD_YEARLY, 'duration_days' => 365,
            'features' => ['ميزة'],
        ]);
    }

    protected function user(string $role): User
    {
        $u = User::create([
            'company_id' => $this->company->id, 'name' => $role,
            'email' => uniqid() . '@x.test', 'password' => bcrypt('secret123'), 'is_active' => true,
        ]);
        $u->assignRole($role);

        return $u;
    }

    public function test_company_admin_sees_self_service_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('company.subscription'))
            ->assertOk()->assertSee('اختر باقة');
    }

    public function test_subscription_request_notifies_platform_admins(): void
    {
        $super = User::create([
            'company_id' => null, 'name' => 'Super', 'email' => uniqid() . '@ops.test',
            'password' => bcrypt('secret123'), 'is_active' => true,
        ]);
        $super->assignRole(User::ROLE_SUPER_ADMIN);

        $this->actingAs($this->admin)
            ->post(route('company.subscription.request'), ['plan_id' => $this->plan->id])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $super->id]);
    }

    public function test_non_admin_cannot_access(): void
    {
        $tech = $this->user(User::ROLE_TECHNICIAN);
        $this->actingAs($tech)->get(route('company.subscription'))->assertForbidden();
    }
}
