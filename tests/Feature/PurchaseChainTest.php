<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\SparePart;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ProcurementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseChainTest extends TestCase
{
    use DatabaseTransactions;

    protected ProcurementService $svc;
    protected Company $company;
    protected Department $division;
    protected Department $dept;
    protected User $divisionHead;
    protected User $deptHead;
    protected User $finance;
    protected User $warehouse;
    protected SparePart $part;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(ProcurementService::class);
        $this->company = Company::create(['name' => 'Chain Co', 'code' => 'CH' . rand(1000, 9999)]);

        $this->division = Department::create(['company_id' => $this->company->id, 'name' => 'Operations', 'type' => 'general']);
        $this->dept = Department::create(['company_id' => $this->company->id, 'name' => 'Electrical', 'type' => 'electrical', 'parent_id' => $this->division->id]);

        $this->divisionHead = $this->user(User::ROLE_DEPARTMENT_HEAD, $this->division->id);
        $this->division->update(['head_id' => $this->divisionHead->id]);
        $this->deptHead = $this->user(User::ROLE_DEPARTMENT_HEAD, $this->dept->id);
        $this->dept->update(['head_id' => $this->deptHead->id]);

        $this->finance = $this->user(User::ROLE_FINANCE_MANAGER, null);
        $this->warehouse = $this->user(User::ROLE_WAREHOUSE_MANAGER, null);

        $this->part = SparePart::create(['company_id' => $this->company->id, 'name' => 'Breaker', 'part_number' => 'PN-' . uniqid(), 'quantity' => 0, 'unit_price' => 100]);
    }

    protected function user(string $role, ?int $deptId): User
    {
        $u = User::create(['company_id' => $this->company->id, 'department_id' => $deptId, 'name' => $role . uniqid(), 'email' => uniqid() . '@chain.local', 'password' => bcrypt('password')]);
        $u->assignRole($role);

        return $u;
    }

    public function test_chain_escalates_up_tree_then_finance_then_received(): void
    {
        // Dept head raises a request for their own department.
        $pr = $this->svc->createManual($this->deptHead, [
            'department_id' => $this->dept->id,
            'fulfillment_type' => PurchaseRequest::TYPE_STOCK,
        ], [['spare_part_id' => $this->part->id, 'quantity' => 5, 'unit_price' => 100]]);
        $this->svc->submit($pr, $this->deptHead);

        // Own level is auto-skipped -> waiting on the PARENT division head.
        $pr->refresh();
        $this->assertEquals(PurchaseRequest::STATUS_PENDING_DEPT, $pr->status);
        $this->assertEquals($this->division->id, $pr->current_dept_id);

        // The requesting dept head is NOT the approver here.
        $this->assertFalse($this->deptHead->can('decide', $pr));
        $this->assertTrue($this->divisionHead->can('decide', $pr));

        // Division head approves -> finance stage.
        $this->svc->approve($pr->fresh(), $this->divisionHead);
        $this->assertEquals(PurchaseRequest::STATUS_PENDING_FINANCE, $pr->fresh()->status);
        $this->assertTrue($this->finance->can('decide', $pr->fresh()));

        // Finance approves -> approved.
        $this->svc->approve($pr->fresh(), $this->finance);
        $this->assertEquals(PurchaseRequest::STATUS_APPROVED, $pr->fresh()->status);

        // Warehouse receives into stock.
        $this->svc->receive($pr->fresh(), $this->warehouse);
        $this->assertEquals(PurchaseRequest::STATUS_RECEIVED, $pr->fresh()->status);
        $this->assertEquals(5, $this->part->fresh()->quantity);
        $this->assertEquals(2, $pr->fresh()->approvals()->where('decision', 'approved')->count());
    }

    public function test_direct_purchase_charges_ticket_without_stock(): void
    {
        $ticket = Ticket::create([
            'company_id' => $this->company->id, 'ticket_number' => 'TKT-' . uniqid(), 'title' => 't',
            'department_id' => $this->dept->id, 'status' => Ticket::STATUS_IN_PROGRESS, 'progress' => 20,
        ]);

        $pr = $this->svc->createManual($this->deptHead, [
            'department_id' => $this->dept->id,
            'ticket_id' => $ticket->id,
            'fulfillment_type' => PurchaseRequest::TYPE_DIRECT,
        ], [['spare_part_id' => $this->part->id, 'quantity' => 2, 'unit_price' => 100]]);
        $this->svc->submit($pr, $this->deptHead);
        $this->svc->approve($pr->fresh(), $this->divisionHead);
        $this->svc->approve($pr->fresh(), $this->finance);
        $this->svc->receive($pr->fresh(), $this->warehouse);

        // Charged to the ticket, but stock is untouched (urgent direct purchase).
        $this->assertEquals(PurchaseRequest::STATUS_RECEIVED, $pr->fresh()->status);
        $this->assertEquals(0, $this->part->fresh()->quantity);
        $this->assertDatabaseHas('ticket_spare_parts', ['ticket_id' => $ticket->id, 'spare_part_id' => $this->part->id, 'quantity_used' => 2]);
    }

    public function test_rejection_records_in_chain(): void
    {
        $pr = $this->svc->createManual($this->deptHead, ['department_id' => $this->dept->id], [['spare_part_id' => $this->part->id, 'quantity' => 1]]);
        $this->svc->submit($pr, $this->deptHead);
        $this->svc->reject($pr->fresh(), $this->divisionHead, 'تجاوز الميزانية');

        $this->assertEquals(PurchaseRequest::STATUS_REJECTED, $pr->fresh()->status);
        $this->assertDatabaseHas('purchase_approvals', ['purchase_request_id' => $pr->id, 'decision' => 'rejected']);
    }
}
