<?php

namespace App\Http\Controllers;

use App\Models\PartRequest;
use App\Models\Ticket;
use App\Services\PartRequestWorkflowService;
use App\Services\ProcurementService;
use Illuminate\Http\Request;

class PartRequestController extends Controller
{
    public function __construct(
        protected PartRequestWorkflowService $workflow,
        protected ProcurementService $procurement,
    ) {
    }

    /** Role-aware queue: head sees pending approvals; warehouse sees approved-to-issue. */
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isDepartmentHead() || $user->isWarehouseManager(), 403);

        $with = ['ticket', 'requester', 'department', 'items.sparePart'];

        $pendingApprovals = ($user->isAdmin() || $user->isDepartmentHead())
            ? PartRequest::with($with)
                ->where('status', PartRequest::STATUS_PENDING)
                ->when(! $user->isAdmin(), fn ($q) => $q->whereIn('department_id', $user->headedDepartments()->pluck('id')))
                ->latest()->get()
            : collect();

        $toIssue = $user->canManageInventory()
            ? PartRequest::with($with)
                ->whereIn('status', [PartRequest::STATUS_APPROVED, PartRequest::STATUS_PARTIAL])
                ->latest()->get()
            : collect();

        return view('part-requests.index', compact('pendingApprovals', 'toIssue'));
    }

    /** Technician raises a request from a ticket. */
    public function store(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);

        $data = $request->validate([
            'parts' => ['required', 'array', 'min:1'],
            'parts.*.spare_part_id' => ['nullable', 'exists:spare_parts,id'],
            'parts.*.custom_name' => ['nullable', 'string', 'max:255'],
            'parts.*.quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'parts.*.quantity.required' => 'الكمية مطلوبة لكل صنف.',
        ]);

        $pr = $this->workflow->create($ticket, $request->user(), $data['parts'], $data['note'] ?? null);

        return back()->with('success', 'تم إنشاء طلب صرف الإسبير ' . $pr->request_number . ' وإرساله للاعتماد.');
    }

    public function approve(Request $request, PartRequest $partRequest)
    {
        $this->authorize('approve', $partRequest);

        $data = $request->validate([
            'approved' => ['nullable', 'array'],
            'approved.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->workflow->approve($partRequest, $request->user(), $data['approved'] ?? []);

        return back()->with('success', 'تم اعتماد الطلب وإرساله للمخزون للصرف.');
    }

    public function reject(Request $request, PartRequest $partRequest)
    {
        $this->authorize('approve', $partRequest);

        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->workflow->reject($partRequest, $request->user(), $data['reason']);

        return back()->with('success', 'تم رفض الطلب.');
    }

    public function issue(Request $request, PartRequest $partRequest)
    {
        $this->authorize('issue', $partRequest);

        $data = $request->validate([
            'issue' => ['nullable', 'array'],
            'issue.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->workflow->issue($partRequest, $request->user(), $data['issue'] ?? []);

        return back()->with('success', 'تم صرف قطع الغيار وتحديث المخزون.');
    }

    public function cancel(Request $request, PartRequest $partRequest)
    {
        $this->authorize('cancel', $partRequest);
        $this->workflow->cancel($partRequest, $request->user());

        return back()->with('success', 'تم إلغاء الطلب.');
    }

    /** Warehouse converts unavailable/custom lines into a purchase request. */
    public function convert(Request $request, PartRequest $partRequest)
    {
        $this->authorize('issue', $partRequest); // warehouse / admin
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        $pr = $this->procurement->convert($partRequest, $request->user(), $data['notes'] ?? null);

        return back()->with('success', 'تم تحويل النقص إلى طلب شراء ' . $pr->request_number . ' وإرساله للمالية.');
    }
}
