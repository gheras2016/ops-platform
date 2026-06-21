<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartRequestResource;
use App\Models\PartRequest;
use App\Services\PartRequestWorkflowService;
use Illuminate\Http\Request;

/**
 * Spare-part request approvals for the mobile app — the missing "اعتمادات" step:
 * the department head approves/rejects a request, and the warehouse manager
 * issues the parts from stock. Reuses PartRequestWorkflowService + the same
 * PartRequestPolicy as the web. Quantities default to the full requested /
 * outstanding amounts (the mobile keeps it one-tap; partial issue stays on web).
 */
class PartRequestActionController extends Controller
{
    public function __construct(protected PartRequestWorkflowService $workflow)
    {
    }

    /** Role-aware queue: pending approvals (head) + ready-to-issue (warehouse). */
    public function inbox(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isDepartmentHead() || $user->isWarehouseManager(), 403);

        $with = ['ticket', 'requester', 'department', 'items.sparePart'];

        $pending = ($user->isAdmin() || $user->isDepartmentHead())
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

        return response()->json([
            'pending_approvals' => PartRequestResource::collection($pending)->toArray($request),
            'to_issue' => PartRequestResource::collection($toIssue)->toArray($request),
        ]);
    }

    public function approve(Request $request, PartRequest $partRequest)
    {
        $this->authorize('approve', $partRequest);
        $this->workflow->approve($partRequest, $request->user());

        return $this->respond($partRequest);
    }

    public function reject(Request $request, PartRequest $partRequest)
    {
        $this->authorize('approve', $partRequest);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->workflow->reject($partRequest, $request->user(), $data['reason']);

        return $this->respond($partRequest);
    }

    public function issue(Request $request, PartRequest $partRequest)
    {
        $this->authorize('issue', $partRequest);
        $this->workflow->issue($partRequest, $request->user());

        return $this->respond($partRequest);
    }

    public function cancel(Request $request, PartRequest $partRequest)
    {
        $this->authorize('cancel', $partRequest);
        $this->workflow->cancel($partRequest, $request->user());

        return $this->respond($partRequest);
    }

    private function respond(PartRequest $partRequest)
    {
        $partRequest->refresh()->load(['ticket', 'requester', 'department', 'items.sparePart']);

        return new PartRequestResource($partRequest);
    }
}
