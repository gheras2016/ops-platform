<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\SparePart;
use App\Models\SpareCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SparePartScopeTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'SP Co', 'code' => 'SP' . rand(1000, 9999)]);
    }

    protected function user(string $role): User
    {
        $u = User::create(['company_id' => $this->company->id, 'name' => $role, 'email' => uniqid() . '@sp.local', 'password' => bcrypt('password')]);
        $u->assignRole($role);

        return $u;
    }

    protected function part(?int $categoryId): SparePart
    {
        return SparePart::create([
            'company_id' => $this->company->id,
            'category_id' => $categoryId,
            'name' => 'part-' . uniqid(),
            'part_number' => 'PN-' . uniqid(),
            'quantity' => 5,
        ]);
    }

    public function test_for_department_scope_returns_only_department_and_global_parts(): void
    {
        $it = Department::create(['company_id' => $this->company->id, 'name' => 'IT', 'type' => 'it']);
        $mec = Department::create(['company_id' => $this->company->id, 'name' => 'MEC', 'type' => 'mechanical']);

        $globalCat = SpareCategory::create(['company_id' => $this->company->id, 'department_id' => null, 'name' => 'Global']);
        $itCat = SpareCategory::create(['company_id' => $this->company->id, 'department_id' => $it->id, 'name' => 'IT']);
        $mecCat = SpareCategory::create(['company_id' => $this->company->id, 'department_id' => $mec->id, 'name' => 'MEC']);

        $globalPart = $this->part($globalCat->id);
        $itPart = $this->part($itCat->id);
        $mecPart = $this->part($mecCat->id);
        $uncategorised = $this->part(null);

        $visible = SparePart::forDepartment($it->id)->pluck('id');

        $this->assertContains($globalPart->id, $visible);      // shared
        $this->assertContains($itPart->id, $visible);          // own department
        $this->assertContains($uncategorised->id, $visible);   // no category = global
        $this->assertNotContains($mecPart->id, $visible);      // other department hidden
    }

    public function test_warehouse_manager_can_access_inventory(): void
    {
        $wm = $this->user(User::ROLE_WAREHOUSE_MANAGER);

        $this->actingAs($wm)->get('/spare-parts')->assertOk();
        $this->actingAs($wm)->get('/spare-categories')->assertOk();
        $this->actingAs($wm)->get('/spare-categories/create')->assertOk();
    }

    public function test_requester_cannot_access_inventory(): void
    {
        $req = $this->user(User::ROLE_REQUESTER);

        $this->actingAs($req)->get('/spare-parts')->assertForbidden();
        $this->actingAs($req)->get('/spare-categories')->assertForbidden();
    }
}
