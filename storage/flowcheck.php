<?php

use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use App\Services\PartRequestWorkflowService;
use App\Services\ProcurementService;

$company = Company::where('code', 'RAWAD')->first();

// A technician in an operational department + an in-progress ticket in that dept.
$tech = User::where('company_id', $company->id)->role(User::ROLE_TECHNICIAN)->first();
$dept = $tech->department;
$head = User::find($dept->head_id);

echo "Dept: {$dept->name} | parent_id: " . ($dept->parent_id ?? 'NULL') . " | head: {$head->name}\n";

$ticket = Ticket::withoutGlobalScopes()
    ->where('company_id', $company->id)
    ->where('department_id', $dept->id)
    ->first();
echo "Ticket: {$ticket->ticket_number} (status {$ticket->status})\n";

$parts = app(PartRequestWorkflowService::class);
$proc = app(ProcurementService::class);

// 1) Technician requests an out-of-catalog (custom) spare.
$pr = $parts->create($ticket, $tech, [['custom_name' => 'صمام خاص جداً', 'quantity' => 2]], 'اختبار التدفق');
echo "\nPartRequest {$pr->request_number} created (status {$pr->status})\n";

// 2) Department head approves it.
$parts->approve($pr->fresh('items'), $head);
$purchase = $pr->fresh()->purchaseRequest;

if (! $purchase) {
    echo "!! NO purchase request was auto-created.\n";
    return;
}
$curDept = $purchase->current_dept_id ? \App\Models\Department::withoutGlobalScopes()->find($purchase->current_dept_id) : null;
echo "After HEAD approval → PurchaseRequest {$purchase->request_number} status: {$purchase->status}";
echo $curDept ? " (waiting on dept: {$curDept->name})\n" : "\n";

// 3) If it stopped at a dept level, approve as that dept's head and re-check.
if ($purchase->fresh()->canDeptDecide()) {
    $stageHead = User::find($curDept->head_id);
    echo "   stage approver: {$stageHead->name}\n";
    $proc->approve($purchase->fresh(), $stageHead);
    $p2 = $purchase->fresh();
    $cur2 = $p2->current_dept_id ? \App\Models\Department::withoutGlobalScopes()->find($p2->current_dept_id) : null;
    echo "After OPS-MANAGER approval → status: {$p2->status}" . ($cur2 ? " (waiting on: {$cur2->name})\n" : "\n");
    echo "   reaches finance? " . ($p2->status === \App\Models\PurchaseRequest::STATUS_PENDING_FINANCE ? "YES ✓" : "NO ✗") . "\n";
}
