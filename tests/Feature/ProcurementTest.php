<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\PartRequest;
use App\Models\PurchaseRequest;
use App\Models\SparePart;
use App\Models\Ticket;
use App\Models\User;
use App\Services\PartRequestWorkflowService;
use App\Services\ProcurementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProcurementTest extends TestCase
{
    use DatabaseTransactions;

    protected PartRequestWorkflowService $parts;
    protected ProcurementService $proc;
    protected Company $company;
    protected Department $dept;
    protected User $head;
    protected User $tech;
    protected User $warehouse;
    protected User $finance;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parts = app(PartRequestWorkflowService::class);
        $this->proc = app(ProcurementService::class);

        $this->company = Company::create(['name' => 'Proc Co', 'code' => 'PC' . rand(1000, 9999)]);
        $this->dept = Department::create(['company_id' => $this->company->id, 'name' => 'Maint', 'type' => 'maintenance']);
        $this->head = $this->user(User::ROLE_DEPARTMENT_HEAD);
        $this->dept->update(['head_id' => $this->head->id]);
        $this->tech = $this->user(User::ROLE_TECHNICIAN);
        $this->warehouse = $this->user(User::ROLE_WAREHOUSE_MANAGER);
        $this->finance = $this->user(User::ROLE_FINANCE_MANAGER);

        $this->ticket = Ticket::create([
            'company_id' => $this->company->id,
            'ticket_number' => 'TKT-' . uniqid(),
            'title' => 'pump',
            'department_id' => $this->dept->id,
            'assigned_to' => $this->tech->id,
            'status' => Ticket::STATUS_IN_PROGRESS,
            'progress' => 30,
            'started_at' => now(),
        ]);
    }

    protected function user(string $role): User
    {
        $u = User::create([
            'company_id' => $this->company->id,
            'department_id' => $this->dept->id ?? null,
            'name' => $role . uniqid(),
            'email' => uniqid() . '@proc.local',
            'password' => bcrypt('password'),
        ]);
        $u->assignRole($role);

        return $u;
    }

    /** Drive a purchase request through dept approval(s) + finance to APPROVED. */
    protected function walkToApproved(PurchaseRequest $purchase): void
    {
        $g = 0;
        while ($purchase->fresh()->canDeptDecide() && $g++ < 5) {
            $dept = \App\Models\Department::find($purchase->fresh()->current_dept_id);
            $this->proc->approve($purchase->fresh(), \App\Models\User::find($dept->head_id));
        }
        if ($purchase->fresh()->canFinanceDecide()) {
            $this->proc->approve($purchase->fresh(), $this->finance);
        }
    }

    public function test_custom_part_flows_through_procurement_into_catalog(): void
    {
        $pr = $this->parts->create($this->ticket, $this->tech, [['custom_name' => 'مضخة خاصة', 'quantity' => 2]]);
        $this->parts->approve($pr->fresh('items'), $this->head);

        // Auto-routed to procurement on head approval (head's own level auto-skipped → finance next).
        $purchase = $pr->fresh()->purchaseRequest;
        $this->assertNotNull($purchase);
        $this->assertEquals(PurchaseRequest::STATUS_PENDING_FINANCE, $purchase->status);
        $this->assertDatabaseHas('purchase_request_items', ['purchase_request_id' => $purchase->id, 'custom_name' => 'مضخة خاصة', 'quantity' => 2]);

        $this->walkToApproved($purchase);
        $this->assertEquals(PurchaseRequest::STATUS_APPROVED, $purchase->fresh()->status);

        $this->proc->receive($purchase->fresh('items'), $this->warehouse);
        $this->assertEquals(PurchaseRequest::STATUS_RECEIVED, $purchase->fresh()->status);

        // A catalog part was created and stocked; the custom line is now linked + issuable.
        $newPart = SparePart::where('company_id', $this->company->id)->where('name', 'مضخة خاصة')->first();
        $this->assertNotNull($newPart);
        $this->assertEquals(2, $newPart->quantity);
        $this->assertDatabaseHas('stock_transactions', ['spare_part_id' => $newPart->id, 'type' => 'in', 'quantity' => 2]);
        $this->assertEquals($newPart->id, $pr->fresh('items')->items->first()->spare_part_id);
        $this->assertEquals(PartRequest::STATUS_APPROVED, $pr->fresh()->status); // re-opened for issuing
    }

    public function test_out_of_stock_catalog_part_is_procured_and_restocked(): void
    {
        $part = SparePart::create([
            'company_id' => $this->company->id, 'name' => 'Seal', 'part_number' => 'PN-' . uniqid(),
            'quantity' => 0, 'min_stock' => 1,
        ]);

        $pr = $this->parts->create($this->ticket, $this->tech, [['spare_part_id' => $part->id, 'quantity' => 3]]);
        $this->parts->approve($pr->fresh('items'), $this->head);

        // Out-of-stock catalog line is auto-procured on approval (shortfall = 3 - 0).
        $purchase = $pr->fresh()->purchaseRequest;
        $this->assertNotNull($purchase);
        $this->assertDatabaseHas('purchase_request_items', ['purchase_request_id' => $purchase->id, 'spare_part_id' => $part->id, 'quantity' => 3]);

        $this->walkToApproved($purchase);
        $this->proc->receive($purchase->fresh('items'), $this->warehouse);

        $this->assertEquals(3, $part->fresh()->quantity); // restocked
        $this->assertEquals(PartRequest::STATUS_APPROVED, $pr->fresh()->status);
    }

    public function test_finance_reject_returns_part_request(): void
    {
        $part = SparePart::create(['company_id' => $this->company->id, 'name' => 'X', 'part_number' => 'PN-' . uniqid(), 'quantity' => 0]);
        $pr = $this->parts->create($this->ticket, $this->tech, [['spare_part_id' => $part->id, 'quantity' => 2]]);
        $this->parts->approve($pr->fresh('items'), $this->head);

        // Auto-routed PR is at finance (head level skipped). Finance rejects it.
        $purchase = $pr->fresh()->purchaseRequest;
        $this->proc->reject($purchase->fresh(), $this->finance, 'تجاوز الميزانية');

        $this->assertEquals(PurchaseRequest::STATUS_REJECTED, $purchase->fresh()->status);
        $this->assertEquals(PartRequest::STATUS_APPROVED, $pr->fresh()->status);
    }

    public function test_authorization(): void
    {
        $this->assertTrue($this->finance->canApprovePurchasing());
        $this->assertFalse($this->warehouse->canApprovePurchasing());
        $this->assertTrue($this->warehouse->canManageInventory());
        $this->assertFalse($this->finance->canManageInventory());
    }
}
