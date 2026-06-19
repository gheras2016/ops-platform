<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\PartRequest;
use App\Models\SpareCategory;
use App\Models\SparePart;
use App\Models\StockTransaction;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiInventoryTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Department $dept;
    protected User $tech;
    protected User $requester;
    protected SparePart $part;
    protected SparePart $lowPart;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Inv Co', 'code' => 'IC' . rand(1000, 9999)]);
        $this->dept = Department::create(['company_id' => $this->company->id, 'name' => 'Maint', 'type' => 'maintenance']);
        $this->tech = $this->user(User::ROLE_TECHNICIAN);
        $this->requester = $this->user(User::ROLE_REQUESTER);

        $this->part = SparePart::create([
            'company_id' => $this->company->id,
            'name' => 'Pump seal',
            'part_number' => 'PN-SEAL-' . rand(1000, 9999),
            'quantity' => 10,
            'min_stock' => 2,
            'unit_price' => 50,
        ]);

        $this->lowPart = SparePart::create([
            'company_id' => $this->company->id,
            'name' => 'V-belt',
            'part_number' => 'PN-BELT-' . rand(1000, 9999),
            'quantity' => 1,
            'min_stock' => 5,
        ]);
    }

    protected function user(string $role): User
    {
        $u = User::create([
            'company_id' => $this->company->id,
            'department_id' => $this->dept->id,
            'name' => $role . uniqid(),
            'email' => uniqid() . '@inv.local',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);
        $u->assignRole($role);

        return $u;
    }

    public function test_technician_can_browse_inventory(): void
    {
        Sanctum::actingAs($this->tech);
        $this->getJson('/api/v1/inventory')->assertOk()->assertJsonStructure([
            'data' => [['id', 'name', 'quantity', 'available', 'reserved', 'low_stock', 'out_of_stock']],
        ]);
    }

    public function test_requester_is_forbidden(): void
    {
        Sanctum::actingAs($this->requester);
        $this->getJson('/api/v1/inventory')->assertForbidden();
    }

    public function test_search_filters_by_name_or_number(): void
    {
        Sanctum::actingAs($this->tech);
        $res = $this->getJson('/api/v1/inventory?q=belt')->assertOk();

        $names = collect($res->json('data'))->pluck('name');
        $this->assertTrue($names->contains('V-belt'));
        $this->assertFalse($names->contains('Pump seal'));
    }

    public function test_low_stock_endpoint_returns_only_low_items(): void
    {
        Sanctum::actingAs($this->tech);
        $res = $this->getJson('/api/v1/inventory/low-stock')->assertOk();

        $ids = collect($res->json('data'))->pluck('id');
        $this->assertContains($this->lowPart->id, $ids);
        $this->assertNotContains($this->part->id, $ids);
    }

    public function test_reserved_and_available_reflect_active_part_request(): void
    {
        // An approved part request reserves 3 of the pump seal.
        $ticket = Ticket::create([
            'company_id' => $this->company->id,
            'department_id' => $this->dept->id,
            'ticket_number' => 'TKT-INV-' . rand(1000, 9999),
            'title' => 'x',
            'status' => Ticket::STATUS_IN_PROGRESS,
        ]);
        $pr = PartRequest::create([
            'company_id' => $this->company->id,
            'request_number' => 'PRQ-' . uniqid(),
            'ticket_id' => $ticket->id,
            'status' => PartRequest::STATUS_APPROVED,
        ]);
        $pr->items()->create(['spare_part_id' => $this->part->id, 'qty_requested' => 3, 'qty_approved' => 3, 'qty_issued' => 0]);

        Sanctum::actingAs($this->tech);
        $res = $this->getJson("/api/v1/inventory/{$this->part->id}")->assertOk();

        $this->assertEquals(3, $res->json('data.reserved'));
        $this->assertEquals(7, $res->json('data.available')); // 10 - 3
        $this->assertTrue($res->json('data.has_open_request'));
    }

    public function test_detail_includes_recent_movements(): void
    {
        $ticket = Ticket::create([
            'company_id' => $this->company->id,
            'department_id' => $this->dept->id,
            'ticket_number' => 'TKT-MOV-' . rand(1000, 9999),
            'title' => 'y',
            'status' => Ticket::STATUS_CLOSED,
        ]);
        StockTransaction::create([
            'company_id' => $this->company->id,
            'spare_part_id' => $this->part->id,
            'type' => 'out',
            'quantity' => 2,
            'related_ticket_id' => $ticket->id,
            'created_by' => $this->tech->id,
        ]);

        Sanctum::actingAs($this->tech);
        $res = $this->getJson("/api/v1/inventory/{$this->part->id}")->assertOk();
        $move = $res->json('data.recent_movements.0');

        $this->assertEquals('out', $move['type']);
        $this->assertEquals(2, $move['quantity']);
        $this->assertEquals($ticket->ticket_number, $move['ticket']['number']);
    }

    public function test_movements_endpoint_paginates_history(): void
    {
        StockTransaction::create([
            'company_id' => $this->company->id,
            'spare_part_id' => $this->part->id, 'type' => 'in', 'quantity' => 20, 'created_by' => $this->tech->id,
        ]);

        Sanctum::actingAs($this->tech);
        $this->getJson("/api/v1/inventory/{$this->part->id}/movements")
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'type', 'type_label', 'quantity']], 'meta' => ['current_page']]);
    }

    public function test_summary_counts(): void
    {
        Sanctum::actingAs($this->tech);
        $res = $this->getJson('/api/v1/inventory/summary')->assertOk();

        $this->assertGreaterThanOrEqual(2, $res->json('total_parts'));
        $this->assertGreaterThanOrEqual(1, $res->json('low_stock_count'));
    }

    public function test_inventory_is_company_scoped(): void
    {
        $other = Company::create(['name' => 'Other', 'code' => 'OC' . rand(1000, 9999)]);
        $hidden = SparePart::create(['company_id' => $other->id, 'name' => 'Secret', 'part_number' => 'H-' . uniqid(), 'quantity' => 9]);

        Sanctum::actingAs($this->tech);
        $res = $this->getJson('/api/v1/inventory')->assertOk();
        $this->assertNotContains($hidden->id, collect($res->json('data'))->pluck('id'));

        // Direct access to another company's part is a 404 (out of scope).
        $this->getJson("/api/v1/inventory/{$hidden->id}")->assertNotFound();
    }
}
