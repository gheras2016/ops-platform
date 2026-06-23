<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlanManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function superAdmin(): User
    {
        $u = User::create([
            'company_id' => null, 'name' => 'Super', 'email' => uniqid() . '@ops.test',
            'password' => bcrypt('secret123'), 'is_active' => true,
        ]);
        $u->assignRole(User::ROLE_SUPER_ADMIN);

        return $u;
    }

    public function test_super_admin_can_create_a_plan(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('plans.store'), [
                'name' => 'الباقة الفضية', 'price' => 499, 'currency' => 'SAR',
                'billing_period' => 'monthly', 'duration_days' => 30, 'sort' => 1,
                'features' => "ميزة أولى\nميزة ثانية", 'is_active' => '1',
            ])->assertRedirect();

        $plan = Plan::where('name', 'الباقة الفضية')->firstOrFail();
        $this->assertEquals(['ميزة أولى', 'ميزة ثانية'], $plan->features);
        $this->assertTrue($plan->is_active);
    }

    public function test_super_admin_can_update_and_toggle(): void
    {
        $plan = Plan::create([
            'name' => 'P', 'slug' => 'p-' . uniqid(), 'price' => 100,
            'billing_period' => 'monthly', 'duration_days' => 30,
        ]);
        $admin = $this->superAdmin();

        // No is_active checkbox in the payload → deactivated (standard checkbox semantics).
        $this->actingAs($admin)->put(route('plans.update', $plan), [
            'name' => 'P+', 'price' => 150, 'currency' => 'SAR',
            'billing_period' => 'yearly', 'duration_days' => 365,
        ])->assertRedirect();
        $this->assertEquals(150, (float) $plan->refresh()->price);
        $this->assertFalse($plan->refresh()->is_active);

        // Toggle flips it back on.
        $this->actingAs($admin)->post(route('plans.toggle', $plan))->assertRedirect();
        $this->assertTrue($plan->refresh()->is_active);
    }

    public function test_non_super_admin_is_forbidden(): void
    {
        $company = \App\Models\Company::create(['name' => 'C', 'code' => 'C' . rand(1000, 9999)]);
        $admin = User::create([
            'company_id' => $company->id, 'name' => 'A', 'email' => uniqid() . '@x.test',
            'password' => bcrypt('secret123'), 'is_active' => true,
        ]);
        $admin->assignRole(User::ROLE_COMPANY_ADMIN);

        $this->actingAs($admin)->get(route('plans.index'))->assertForbidden();
    }
}
