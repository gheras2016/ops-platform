<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RouteSmokeTest extends TestCase
{
    use DatabaseTransactions;

    protected function admin(): User
    {
        $company = Company::create(['name' => 'Smoke Co', 'code' => 'SMK' . rand(1000, 9999)]);
        Department::create(['company_id' => $company->id, 'name' => 'Maint', 'type' => 'maintenance']);
        $u = User::create([
            'company_id' => $company->id,
            'name' => 'Admin',
            'email' => uniqid() . '@smoke.local',
            'password' => bcrypt('password'),
        ]);
        $u->assignRole(User::ROLE_COMPANY_ADMIN);

        return $u;
    }

    public function test_admin_pages_load(): void
    {
        $admin = $this->admin();

        $routes = [
            '/dashboard', '/tickets', '/tickets/create', '/reports',
            '/departments', '/departments/create', '/users', '/users/create',
            '/locations', '/locations/create',
            '/spare-parts', '/spare-parts/create',
            '/inventory/items', '/inventory/categories',
            '/stock-transactions', '/stock-transactions/create',
            '/purchase-requests', '/purchase-requests/create',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)->get($route)->assertOk();
        }
    }

    public function test_super_admin_sees_companies(): void
    {
        $u = User::create(['name' => 'Super', 'email' => uniqid() . '@smoke.local', 'password' => bcrypt('password')]);
        $u->assignRole(User::ROLE_SUPER_ADMIN);

        $this->actingAs($u)->get('/companies')->assertOk();
        $this->actingAs($u)->get('/companies/create')->assertOk();
    }

    public function test_requester_can_open_ticket_form(): void
    {
        $company = Company::create(['name' => 'Req Co', 'code' => 'REQ' . rand(1000, 9999)]);
        Department::create(['company_id' => $company->id, 'name' => 'IT', 'type' => 'it']);
        $u = User::create(['company_id' => $company->id, 'name' => 'Req', 'email' => uniqid() . '@smoke.local', 'password' => bcrypt('password')]);
        $u->assignRole(User::ROLE_REQUESTER);

        $this->actingAs($u)->get('/dashboard')->assertOk();
        $this->actingAs($u)->get('/tickets/create')->assertOk();
        // requester must NOT access admin pages
        $this->actingAs($u)->get('/users')->assertForbidden();
    }
}
