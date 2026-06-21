<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use App\Services\ProcurementService;
use Illuminate\Http\Request;

/**
 * Purchase-request decisions for the mobile app: approve / reject the current
 * stage (department head or finance) and receive (warehouse). Reuses
 * ProcurementService + PurchaseRequestPolicy.
 */
class PurchaseRequestActionController extends Controller
{
    public function __construct(protected ProcurementService $procurement)
    {
    }

    public function approve(Request $request, PurchaseRequest $purchaseRequest)
    {
        $this->authorize('decide', $purchaseRequest);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $this->procurement->approve($purchaseRequest, $request->user(), $data['note'] ?? null);

        return $this->respond($purchaseRequest);
    }

    public function reject(Request $request, PurchaseRequest $purchaseRequest)
    {
        $this->authorize('decide', $purchaseRequest);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->procurement->reject($purchaseRequest, $request->user(), $data['reason']);

        return $this->respond($purchaseRequest);
    }

    public function receive(Request $request, PurchaseRequest $purchaseRequest)
    {
        $this->authorize('receive', $purchaseRequest);
        $this->procurement->receive($purchaseRequest, $request->user());

        return $this->respond($purchaseRequest);
    }

    private function respond(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->refresh()->load([
            'department', 'requester', 'ticket', 'currentDept',
            'items.sparePart', 'approvals.approver', 'approvals.department',
        ]);

        return new PurchaseRequestResource($purchaseRequest);
    }
}
