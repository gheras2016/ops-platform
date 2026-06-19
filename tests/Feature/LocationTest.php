<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Loc Co', 'code' => 'LOC' . rand(1000, 9999)]);
        $this->admin = User::create(['company_id' => $this->company->id, 'name' => 'Admin', 'email' => uniqid() . '@loc.local', 'password' => bcrypt('password')]);
        $this->admin->assignRole(User::ROLE_COMPANY_ADMIN);
    }

    public function test_admin_can_create_nested_location_with_computed_path(): void
    {
        $building = Location::create(['company_id' => $this->company->id, 'name' => 'المبنى أ', 'type' => 'building', 'full_path' => 'المبنى أ']);

        $this->actingAs($this->admin)
            ->post('/locations', ['name' => 'غرفة الخوادم', 'type' => 'room', 'parent_id' => $building->id])
            ->assertRedirect(route('locations.index'));

        $child = Location::where('name', 'غرفة الخوادم')->first();
        $this->assertNotNull($child);
        $this->assertEquals('المبنى أ / غرفة الخوادم', $child->full_path);
        $this->assertEquals($this->company->id, $child->company_id);
    }

    public function test_renaming_parent_refreshes_descendant_paths(): void
    {
        $building = Location::create(['company_id' => $this->company->id, 'name' => 'المبنى أ', 'type' => 'building', 'full_path' => 'المبنى أ']);
        $room = Location::create(['company_id' => $this->company->id, 'name' => 'غرفة 1', 'type' => 'room', 'parent_id' => $building->id, 'full_path' => 'المبنى أ / غرفة 1']);

        $this->actingAs($this->admin)
            ->put("/locations/{$building->id}", ['name' => 'المبنى ب', 'type' => 'building']);

        $this->assertEquals('المبنى ب / غرفة 1', $room->fresh()->full_path);
    }

    public function test_user_can_be_linked_to_a_location(): void
    {
        $loc = Location::create(['company_id' => $this->company->id, 'name' => 'الدور الثاني', 'type' => 'floor', 'full_path' => 'الدور الثاني']);

        $this->actingAs($this->admin)->post('/users', [
            'name' => 'موظف الموقع',
            'email' => uniqid() . '@loc.local',
            'role' => User::ROLE_REQUESTER,
            'location_id' => $loc->id,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['name' => 'موظف الموقع', 'location_id' => $loc->id]);
    }

    public function test_admin_can_quick_create_location_via_ajax(): void
    {
        $building = Location::create(['company_id' => $this->company->id, 'name' => 'المبنى ج', 'type' => 'building', 'full_path' => 'المبنى ج']);

        $this->actingAs($this->admin)
            ->postJson('/locations/quick', ['name' => 'الدور الرابع', 'type' => 'floor', 'parent_id' => $building->id])
            ->assertOk()
            ->assertJson(['name' => 'الدور الرابع', 'full_path' => 'المبنى ج / الدور الرابع']);

        $this->assertDatabaseHas('locations', ['name' => 'الدور الرابع', 'company_id' => $this->company->id]);
    }

    public function test_non_admin_cannot_quick_create_location(): void
    {
        $requester = User::create(['company_id' => $this->company->id, 'name' => 'R', 'email' => uniqid() . '@loc.local', 'password' => bcrypt('password')]);
        $requester->assignRole(User::ROLE_REQUESTER);

        $this->actingAs($requester)
            ->postJson('/locations/quick', ['name' => 'X', 'type' => 'room'])
            ->assertForbidden();
    }

    public function test_non_admin_cannot_access_locations(): void
    {
        $requester = User::create(['company_id' => $this->company->id, 'name' => 'Req', 'email' => uniqid() . '@loc.local', 'password' => bcrypt('password')]);
        $requester->assignRole(User::ROLE_REQUESTER);

        $this->actingAs($requester)->get('/locations')->assertForbidden();
    }
}
