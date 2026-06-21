<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\SpareCategory;
use App\Models\SparePart;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTicketPartsTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Department $dept;
    protected User $head;
    protected User $tech;
    protected User $requester;
    protected SparePart $part;
    protected TicketWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflow = app(TicketWorkflowService::class);

        $this->company = Company::create(['name' => 'Parts Co', 'code' => 'PC' . rand(1000, 9999)]);
        $this->dept = Department::create(['company_id' => $this->company->id, 'name' => 'Maint', 'type' => 'maintenance']);
        $this->head = $this->user(User::ROLE_DEPARTMENT_HEAD);
        $this->dept->update(['head_id' => $this->head->id]);
        $this->tech = $this->user(User::ROLE_TECHNICIAN);
        $this->requester = $this->user(User::ROLE_REQUESTER);

        $this->part = SparePart::create([
            'company_id' => $this->company->id,
            'name' => 'Pump seal',
            'part_number' => 'PN-' . uniqid(),
            'quantity' => 10,
            'min_stock' => 2,
            'unit_price' => 50,
        ]);
    }

    protected function user(string $role): User
    {
        $u = User::create([
            'company_id' => $this->company->id,
            'department_id' => $this->dept->id,
            'name' => $role . uniqid(),
            'email' => uniqid() . '@parts.local',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);
        $u->assignRole($role);

        return $u;
    }

    /** Ticket assigned to the technician and in progress. */
    protected function workingTicket(): Ticket
    {
        $ticket = $this->workflow->create(
            ['company_id' => $this->company->id, 'title' => 'T' . uniqid(), 'department_id' => $this->dept->id],
            $this->requester,
        );
        $this->workflow->assign($ticket->fresh(), $this->tech, $this->head);
        $this->workflow->accept($ticket->fresh(), $this->tech);
        $this->workflow->start($ticket->fresh(), $this->tech);

        return $ticket->fresh();
    }

    public function test_search_returns_company_scoped_catalogue(): void
    {
        $other = Company::create(['name' => 'Other', 'code' => 'OT' . rand(1000, 9999)]);
        SparePart::create(['company_id' => $other->id, 'name' => 'Hidden', 'part_number' => 'X-' . uniqid(), 'quantity' => 5]);

        Sanctum::actingAs($this->tech);
        $res = $this->getJson('/api/v1/spare-parts?q=seal')->assertOk();

        $names = collect($res->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Pump seal'));
        $this->assertFalse($names->contains('Hidden'));
    }

    /** Make a spare part that belongs to ANOTHER department's category. */
    protected function otherDepartmentPart(): SparePart
    {
        $itDept = Department::create(['company_id' => $this->company->id, 'name' => 'IT', 'type' => 'it']);
        $itCat = SpareCategory::create(['company_id' => $this->company->id, 'department_id' => $itDept->id, 'name' => 'IT cat']);

        return SparePart::create([
            'company_id' => $this->company->id,
            'category_id' => $itCat->id,
            'name' => 'IT Router',
            'part_number' => 'PN-IT-' . rand(1000, 9999),
            'quantity' => 5,
        ]);
    }

    public function test_picker_is_scoped_to_technician_department(): void
    {
        $this->otherDepartmentPart(); // another department's part

        Sanctum::actingAs($this->tech); // technician in the Maintenance department
        $names = collect($this->getJson('/api/v1/spare-parts')->assertOk()->json('data'))->pluck('name');

        $this->assertTrue($names->contains('Pump seal'));  // uncategorised → global → visible
        $this->assertFalse($names->contains('IT Router')); // other department → hidden
    }

    public function test_warehouse_manager_sees_all_departments_parts(): void
    {
        $this->otherDepartmentPart();
        $warehouse = $this->user(User::ROLE_WAREHOUSE_MANAGER);

        Sanctum::actingAs($warehouse);
        $names = collect($this->getJson('/api/v1/spare-parts')->assertOk()->json('data'))->pluck('name');

        $this->assertTrue($names->contains('IT Router')); // inventory manager → sees everything
    }

    public function test_technician_records_a_used_catalogue_part_without_deducting_stock(): void
    {
        $ticket = $this->workingTicket();

        Sanctum::actingAs($this->tech);
        $this->postJson("/api/v1/tickets/{$ticket->id}/spare-parts", [
            'spare_part_id' => $this->part->id,
            'quantity' => 3,
        ])->assertCreated()
            ->assertJsonPath('data.quantity_used', 3)
            ->assertJsonPath('data.is_deducted', false);

        // Stock untouched until the ticket closes.
        $this->assertEquals(10, $this->part->fresh()->quantity);
        $this->assertDatabaseHas('ticket_spare_parts', [
            'ticket_id' => $ticket->id, 'spare_part_id' => $this->part->id, 'deducted_at' => null,
        ]);
    }

    public function test_used_part_appears_in_ticket_detail(): void
    {
        $ticket = $this->workingTicket();

        Sanctum::actingAs($this->tech);
        $this->postJson("/api/v1/tickets/{$ticket->id}/spare-parts", ['spare_part_id' => $this->part->id, 'quantity' => 2]);

        $res = $this->getJson("/api/v1/tickets/{$ticket->id}")->assertOk();
        $this->assertTrue($res->json('data.can_manage_parts'));
        $this->assertCount(1, $res->json('data.spare_parts'));
        $this->assertEquals(100.0, $res->json('data.parts_cost')); // 2 * 50
    }

    public function test_custom_used_part_is_recorded_by_name(): void
    {
        $ticket = $this->workingTicket();

        Sanctum::actingAs($this->tech);
        $this->postJson("/api/v1/tickets/{$ticket->id}/spare-parts", [
            'custom_name' => 'حساس نادر',
            'quantity' => 1,
            'unit_cost' => 250,
        ])->assertCreated()
            ->assertJsonPath('data.is_custom', true)
            ->assertJsonPath('data.name', 'حساس نادر');
    }

    public function test_pending_used_part_can_be_removed(): void
    {
        $ticket = $this->workingTicket();

        Sanctum::actingAs($this->tech);
        $id = $this->postJson("/api/v1/tickets/{$ticket->id}/spare-parts", ['spare_part_id' => $this->part->id, 'quantity' => 1])
            ->json('data.id');

        $this->deleteJson("/api/v1/tickets/{$ticket->id}/spare-parts/{$id}")->assertOk();
        $this->assertDatabaseMissing('ticket_spare_parts', ['id' => $id]);
    }

    public function test_stock_is_deducted_when_ticket_is_closed(): void
    {
        $ticket = $this->workingTicket();

        Sanctum::actingAs($this->tech);
        $usageId = $this->postJson("/api/v1/tickets/{$ticket->id}/spare-parts", ['spare_part_id' => $this->part->id, 'quantity' => 4])
            ->json('data.id');
        $this->postJson("/api/v1/tickets/{$ticket->id}/resolve", ['resolution_note' => 'done']);

        Sanctum::actingAs($this->head);
        $this->postJson("/api/v1/tickets/{$ticket->id}/approve")->assertOk()->assertJsonPath('data.status', 'closed');

        $this->assertEquals(6, $this->part->fresh()->quantity); // 10 - 4
        $this->assertDatabaseHas('stock_transactions', [
            'spare_part_id' => $this->part->id, 'type' => 'out', 'related_ticket_id' => $ticket->id, 'quantity' => 4,
        ]);

        // A deducted line can no longer be removed.
        Sanctum::actingAs($this->tech);
        $this->deleteJson("/api/v1/tickets/{$ticket->id}/spare-parts/{$usageId}")->assertStatus(422);
    }

    public function test_requester_cannot_record_parts(): void
    {
        $ticket = $this->workingTicket();

        Sanctum::actingAs($this->requester);
        $this->postJson("/api/v1/tickets/{$ticket->id}/spare-parts", ['spare_part_id' => $this->part->id, 'quantity' => 1])
            ->assertForbidden();
    }

    public function test_technician_raises_non_catalogue_part_request(): void
    {
        $ticket = $this->workingTicket();

        Sanctum::actingAs($this->tech);
        $res = $this->postJson("/api/v1/tickets/{$ticket->id}/part-requests", [
            'name' => 'صمام خاص',
            'description' => 'صمام نحاسي 2 إنش غير متوفر بالكتالوج',
            'quantity' => 2,
            'reason' => 'تالف ولا بديل متوفر',
        ])->assertCreated();

        $this->assertEquals('pending', $res->json('data.status'));
        $item = $res->json('data.items.0');
        $this->assertEquals('صمام خاص', $item['name']);
        $this->assertTrue($item['is_custom']);
        $this->assertEquals(2, $item['qty_requested']);

        $this->assertDatabaseHas('part_requests', ['ticket_id' => $ticket->id, 'status' => 'pending']);
        $this->assertDatabaseHas('part_request_items', [
            'custom_name' => 'صمام خاص', 'description' => 'صمام نحاسي 2 إنش غير متوفر بالكتالوج', 'qty_requested' => 2,
        ]);

        // Raising a part request while working PAUSES the ticket until the parts
        // are approved + issued (same behaviour as the web).
        $this->assertEquals(Ticket::STATUS_PAUSED, $ticket->fresh()->status);
        $this->assertDatabaseHas('ticket_pause_logs', ['ticket_id' => $ticket->id, 'reason_code' => 'spare_part']);

        // Linked request is visible on the ticket.
        $list = $this->getJson("/api/v1/tickets/{$ticket->id}/part-requests")->assertOk();
        $this->assertCount(1, $list->json('data'));
    }

    public function test_technician_requests_a_catalogue_part_from_warehouse(): void
    {
        $ticket = $this->workingTicket();

        Sanctum::actingAs($this->tech);
        $res = $this->postJson("/api/v1/tickets/{$ticket->id}/part-requests", [
            'spare_part_id' => $this->part->id,
            'quantity' => 2,
            'reason' => 'مطلوبة لإكمال الإصلاح',
        ])->assertCreated();

        $item = $res->json('data.items.0');
        $this->assertEquals($this->part->id, $item['spare_part_id']);
        $this->assertFalse($item['is_custom']);

        // Requesting parts pauses the ticket until they are approved + issued.
        $this->assertEquals(Ticket::STATUS_PAUSED, $ticket->fresh()->status);
    }
}
