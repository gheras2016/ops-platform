<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\PartRequest;
use App\Models\SparePart;
use App\Models\Ticket;
use App\Models\User;
use App\Services\PartRequestWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PartRequestTest extends TestCase
{
    use DatabaseTransactions;

    protected PartRequestWorkflowService $svc;
    protected Company $company;
    protected Department $dept;
    protected User $head;
    protected User $tech;
    protected User $warehouse;
    protected Ticket $ticket;
    protected SparePart $part;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(PartRequestWorkflowService::class);

        $this->company = Company::create(['name' => 'PR Co', 'code' => 'PR' . rand(1000, 9999)]);
        $this->dept = Department::create(['company_id' => $this->company->id, 'name' => 'Maint', 'type' => 'maintenance']);
        $this->head = $this->makeUser(User::ROLE_DEPARTMENT_HEAD);
        $this->dept->update(['head_id' => $this->head->id]);
        $this->tech = $this->makeUser(User::ROLE_TECHNICIAN);
        $this->warehouse = $this->makeUser(User::ROLE_WAREHOUSE_MANAGER);

        $this->ticket = Ticket::create([
            'company_id' => $this->company->id,
            'ticket_number' => 'TKT-' . uniqid(),
            'title' => 'Broken pump',
            'department_id' => $this->dept->id,
            'assigned_to' => $this->tech->id,
            'status' => Ticket::STATUS_IN_PROGRESS,
            'progress' => 40,
            'started_at' => now(),
        ]);

        $this->part = SparePart::create([
            'company_id' => $this->company->id,
            'name' => 'Pump seal',
            'part_number' => 'PN-' . uniqid(),
            'quantity' => 10,
            'min_stock' => 2,
            'unit_price' => 50,
        ]);
    }

    protected function makeUser(string $role): User
    {
        $u = User::create([
            'company_id' => $this->company->id,
            'department_id' => $this->dept->id ?? null,
            'name' => $role . uniqid(),
            'email' => uniqid() . '@pr.local',
            'password' => bcrypt('password'),
        ]);
        $u->assignRole($role);

        return $u;
    }

    public function test_create_request_pauses_ticket_and_is_pending(): void
    {
        $pr = $this->svc->create($this->ticket, $this->tech, [['spare_part_id' => $this->part->id, 'quantity' => 3]], 'needed');

        $this->assertEquals(PartRequest::STATUS_PENDING, $pr->status);
        $this->assertEquals(3, $pr->items->first()->qty_requested);
        $this->assertEquals(Ticket::STATUS_PAUSED, $this->ticket->fresh()->status);
        $this->assertDatabaseHas('ticket_pause_logs', ['ticket_id' => $this->ticket->id, 'reason_code' => 'spare_part']);
    }

    public function test_approve_reserves_stock(): void
    {
        $pr = $this->svc->create($this->ticket, $this->tech, [['spare_part_id' => $this->part->id, 'quantity' => 3]]);
        $this->svc->approve($pr->fresh('items'), $this->head);

        $this->assertEquals(PartRequest::STATUS_APPROVED, $pr->fresh()->status);
        $this->assertEquals(3, $this->part->fresh()->reservedQty());
        $this->assertEquals(7, $this->part->fresh()->availableQty());
        $this->assertEquals(10, $this->part->fresh()->quantity); // on-hand untouched until issue
    }

    public function test_issue_deducts_stock_and_records_consumption(): void
    {
        $pr = $this->svc->create($this->ticket, $this->tech, [['spare_part_id' => $this->part->id, 'quantity' => 3]]);
        $this->svc->approve($pr->fresh('items'), $this->head);
        $this->svc->issue($pr->fresh('items'), $this->warehouse);

        $fresh = $pr->fresh('items');
        $this->assertEquals(PartRequest::STATUS_ISSUED, $fresh->status);
        $this->assertEquals(3, $fresh->items->first()->qty_issued);
        $this->assertEquals(7, $this->part->fresh()->quantity);       // on-hand reduced
        $this->assertEquals(0, $this->part->fresh()->reservedQty());  // reservation released
        $this->assertDatabaseHas('stock_transactions', ['spare_part_id' => $this->part->id, 'type' => 'out', 'related_ticket_id' => $this->ticket->id, 'quantity' => 3]);
        $this->assertDatabaseHas('ticket_spare_parts', ['ticket_id' => $this->ticket->id, 'spare_part_id' => $this->part->id, 'quantity_used' => 3]);
    }

    public function test_partial_issue_when_short(): void
    {
        $this->part->update(['quantity' => 2]); // less than requested 3
        $pr = $this->svc->create($this->ticket, $this->tech, [['spare_part_id' => $this->part->id, 'quantity' => 3]]);
        $this->svc->approve($pr->fresh('items'), $this->head);
        $this->svc->issue($pr->fresh('items'), $this->warehouse);

        $fresh = $pr->fresh('items');
        $this->assertEquals(PartRequest::STATUS_PARTIAL, $fresh->status);
        $this->assertEquals(2, $fresh->items->first()->qty_issued);
        $this->assertEquals(0, $this->part->fresh()->quantity);
    }

    public function test_authorization_rules(): void
    {
        $pr = $this->svc->create($this->ticket, $this->tech, [['spare_part_id' => $this->part->id, 'quantity' => 1]]);

        $this->assertTrue($this->head->can('approve', $pr));
        $this->assertFalse($this->tech->can('approve', $pr));
        $this->assertTrue($this->warehouse->can('issue', $pr));
        $this->assertFalse($this->tech->can('issue', $pr));
    }

    public function test_warehouse_can_view_ticket_only_when_it_has_a_request(): void
    {
        // No request yet → warehouse cannot view an unrelated ticket.
        $this->assertFalse($this->warehouse->can('view', $this->ticket));

        $this->svc->create($this->ticket, $this->tech, [['spare_part_id' => $this->part->id, 'quantity' => 1]]);

        // Now there's a request to fulfil → access granted.
        $this->assertTrue($this->warehouse->can('view', $this->ticket->fresh()));
    }

    public function test_technician_can_request_a_custom_part_via_http(): void
    {
        $this->actingAs($this->tech)
            ->post("/tickets/{$this->ticket->id}/part-requests", [
                'parts' => [['custom_name' => 'قطعة غير موجودة', 'quantity' => 2]],
                'note' => 'مطلوبة عاجلًا',
            ])
            ->assertRedirect();

        // The custom line (no spare_part_id) must be accepted, not rejected by validation.
        $this->assertDatabaseHas('part_request_items', ['custom_name' => 'قطعة غير موجودة', 'qty_requested' => 2]);
    }

    public function test_report_pdf_renders_with_parts_cost(): void
    {
        \App\Models\TicketSparePart::create([
            'ticket_id' => $this->ticket->id, 'spare_part_id' => $this->part->id,
            'quantity_used' => 3, 'unit_cost' => 50,
        ]);

        $this->assertEquals(150.0, $this->ticket->fresh()->partsCost());

        $admin = User::create(['company_id' => $this->company->id, 'name' => 'A', 'email' => uniqid() . '@pr.local', 'password' => bcrypt('password')]);
        $admin->assignRole(User::ROLE_COMPANY_ADMIN);

        $this->actingAs($admin)->get("/tickets/{$this->ticket->id}/report")
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_cannot_issue_before_approval(): void
    {
        $pr = $this->svc->create($this->ticket, $this->tech, [['spare_part_id' => $this->part->id, 'quantity' => 1]]);

        $this->expectException(ValidationException::class);
        $this->svc->issue($pr->fresh('items'), $this->warehouse);
    }
}
