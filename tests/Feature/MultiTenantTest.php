<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TenantExporter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MultiTenantTest extends TestCase
{
    use DatabaseTransactions;

    protected function superAdmin(): User
    {
        $u = User::create([
            'company_id' => null,
            'name' => 'Platform Owner',
            'email' => uniqid() . '@platform.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $u->assignRole(User::ROLE_SUPER_ADMIN);

        return $u;
    }

    /** Item 1 — creating a company provisions its company_admin in one step. */
    public function test_creating_company_provisions_an_admin(): void
    {
        $email = uniqid() . '@newco.local';

        $this->actingAs($this->superAdmin())
            ->post('/companies', [
                'name' => 'New Co',
                'code' => 'NEWCO' . rand(1000, 9999),
                'is_active' => '1',
                'admin_name' => 'Company Manager',
                'admin_email' => $email,
                'admin_password' => 'secret123',
                'admin_password_confirmation' => 'secret123',
            ])
            ->assertRedirect(route('companies.index'));

        $admin = User::where('email', $email)->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole(User::ROLE_COMPANY_ADMIN));
        $this->assertNotNull($admin->company_id);
        $this->assertSame('New Co', $admin->company->name);
    }

    /** Item 2 — a user in a deactivated company cannot log in. */
    public function test_user_of_deactivated_company_cannot_login(): void
    {
        $company = Company::create(['name' => 'Dead Co', 'code' => 'DEAD' . rand(1000, 9999), 'is_active' => false]);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Stuck',
            'email' => uniqid() . '@dead.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole(User::ROLE_REQUESTER);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /** Item 2 — the active middleware logs out a user deactivated mid-session. */
    public function test_active_middleware_blocks_deactivated_user(): void
    {
        $company = Company::create(['name' => 'Live Co', 'code' => 'LIVE' . rand(1000, 9999), 'is_active' => true]);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Suspended',
            'email' => uniqid() . '@live.local',
            'password' => bcrypt('password'),
            'is_active' => false,
        ]);
        $user->assignRole(User::ROLE_REQUESTER);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /** Item 3 — export gathers only the target company's data, isolated from others. */
    public function test_export_is_isolated_per_company(): void
    {
        $a = Company::create(['name' => 'Alpha', 'code' => 'ALPHA' . rand(1000, 9999), 'is_active' => true]);
        $b = Company::create(['name' => 'Beta', 'code' => 'BETA' . rand(1000, 9999), 'is_active' => true]);

        $deptA = Department::create(['company_id' => $a->id, 'name' => 'A-Dept', 'type' => 'maintenance']);
        $deptB = Department::create(['company_id' => $b->id, 'name' => 'B-Dept', 'type' => 'maintenance']);

        $userA = User::create(['company_id' => $a->id, 'name' => 'ua', 'email' => uniqid() . '@a.local', 'password' => bcrypt('x'), 'is_active' => true]);
        $userB = User::create(['company_id' => $b->id, 'name' => 'ub', 'email' => uniqid() . '@b.local', 'password' => bcrypt('x'), 'is_active' => true]);

        Ticket::create(['company_id' => $a->id, 'ticket_number' => 'A-' . uniqid(), 'title' => 'a ticket', 'department_id' => $deptA->id, 'created_by' => $userA->id, 'status' => 'open']);
        Ticket::create(['company_id' => $b->id, 'ticket_number' => 'B-' . uniqid(), 'title' => 'b ticket', 'department_id' => $deptB->id, 'created_by' => $userB->id, 'status' => 'open']);

        $data = app(TenantExporter::class)->collect($a);

        $this->assertCount(1, $data['tickets']);
        $this->assertSame('a ticket', $data['tickets'][0]->title);
        $this->assertCount(1, $data['users']);
        $this->assertCount(1, $data['departments']);

        // Credential hashes must never be exported.
        $this->assertArrayNotHasKey('password', (array) $data['users'][0]);
    }
}
