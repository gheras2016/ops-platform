<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseRequestResource;
use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\Ticket;
use App\Services\ProcurementService;
use Illuminate\Http\Request;

/**
 * Mobile API for purchase requests — the procurement loop that closes the gap
 * "a request stalls before it reaches finance". Reuses ProcurementService +
 * PurchaseRequestPolicy (same chain as the web): department head(s) → finance →
 * approved → warehouse receives.
 */
class PurchaseRequestController extends Controller
{
    public function __construct(protected ProcurementService $procurement)
    {
    }

    /** Role-aware: requests awaiting MY action + my own requests. */
    public function index(Request $request)
    {
        $user = $request->user();
        $headed = $user->headedDepartments()->pluck('id')->all();
        $with = ['department', 'requester', 'currentDept', 'ticket', 'items'];

        $canAct = $user->isAdmin() || ! empty($headed) || $user->canApprovePurchasing() || $user->canManageInventory();

        $actionable = $canAct
            ? PurchaseRequest::with($with)->where(function ($q) use ($user, $headed) {
                if (! empty($headed)) {
                    $q->orWhere(fn ($w) => $w->where('status', PurchaseRequest::STATUS_PENDING_DEPT)->whereIn('current_dept_id', $headed));
                }
                if ($user->canApprovePurchasing()) {
                    $q->orWhere('status', PurchaseRequest::STATUS_PENDING_FINANCE);
                }
                if ($user->canManageInventory()) {
                    $q->orWhere('status', PurchaseRequest::STATUS_APPROVED);
                }
                if (! $user->canApprovePurchasing() && ! $user->canManageInventory() && empty($headed)) {
                    $q->whereRaw('1 = 0');
                }
            })->latest()->get()
            : collect();

        $mine = PurchaseRequest::with($with)
            ->where('requested_by', $user->id)
            ->latest()->limit(30)->get();

        return response()->json([
            'actionable' => PurchaseRequestResource::collection($actionable)->toArray($request),
            'mine' => PurchaseRequestResource::collection($mine)->toArray($request),
        ]);
    }

    public function show(Request $request, PurchaseRequest $purchaseRequest)
    {
        $this->authorize('view', $purchaseRequest);

        $purchaseRequest->load([
            'department', 'requester', 'ticket', 'currentDept',
            'items.sparePart', 'approvals.approver', 'approvals.department',
        ]);

        return new PurchaseRequestResource($purchaseRequest);
    }

    /** Create + submit a purchase request into the approval chain. */
    public function store(Request $request)
    {
        $this->authorize('create', PurchaseRequest::class);

        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'ticket_id' => ['nullable', 'exists:tickets,id'],
            'fulfillment_type' => ['required', 'in:stock,direct'],
            'justification' => ['nullable', 'string', 'max:2000'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.spare_part_id' => ['nullable', 'exists:spare_parts,id'],
            'items.*.custom_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $pr = $this->procurement->createManual($request->user(), $data, $data['items']);
        $this->procurement->submit($pr, $request->user());

        $pr->load(['department', 'requester', 'ticket', 'currentDept', 'items.sparePart', 'approvals.approver']);

        return (new PurchaseRequestResource($pr))->response()->setStatusCode(201);
    }

    /** Reference data for the create form. */
    public function meta(Request $request)
    {
        $user = $request->user();

        $departments = $user->isAdmin()
            ? Department::orderBy('name')->get(['id', 'name'])
            : $user->headedDepartments()->orderBy('name')->get(['id', 'name']);
        if ($departments->isEmpty() && $user->department) {
            $departments = Department::whereKey($user->department_id)->get(['id', 'name']);
        }

        return response()->json([
            'departments' => $departments->map(fn ($d) => ['id' => $d->id, 'name' => $d->name]),
            'types' => collect(PurchaseRequest::TYPES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'tickets' => Ticket::visibleTo($user)->open()->latest()->limit(50)
                ->get(['id', 'ticket_number', 'title'])
                ->map(fn ($t) => ['id' => $t->id, 'number' => $t->ticket_number, 'title' => $t->title]),
        ]);
    }
}
