<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\SparePart;
use App\Models\Ticket;
use App\Services\ProcurementService;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function __construct(protected ProcurementService $procurement)
    {
    }

    /** Role-aware list: what needs my action + my requests. */
    public function index(Request $request)
    {
        $user = $request->user();
        $headed = $user->headedDepartments()->pluck('id')->all();
        $with = ['department', 'requester', 'currentDept', 'items'];

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
                    $q->whereRaw('1 = 0'); // safety: no actionable scope
                }
            })->latest()->get()
            : collect();

        $mine = PurchaseRequest::with($with)->where('requested_by', $user->id)->latest()->limit(20)->get();

        return view('purchase-requests.index', compact('actionable', 'mine'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', PurchaseRequest::class);
        $user = $request->user();

        $departments = $user->isAdmin()
            ? Department::orderBy('name')->get()
            : $user->headedDepartments()->orderBy('name')->get();
        if ($departments->isEmpty() && $user->department) {
            $departments = Department::whereKey($user->department_id)->get();
        }

        $deptId = $departments->first()?->id;
        $spareParts = SparePart::with('category')
            ->when(! $user->isAdmin() && $deptId, fn ($q) => $q->forDepartment($deptId))
            ->orderBy('name')->get(['id', 'name', 'part_number', 'quantity', 'category_id', 'unit_price']);

        return view('purchase-requests.create', [
            'departments' => $departments,
            'spareParts' => $spareParts,
            'types' => PurchaseRequest::TYPES,
            'tickets' => Ticket::visibleTo($user)->open()->latest()->limit(50)->get(['id', 'ticket_number', 'title']),
        ]);
    }

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

        return redirect()->route('purchase-requests.show', $pr)
            ->with('success', 'تم إنشاء طلب الشراء ' . $pr->request_number . ' وإرساله للاعتماد.');
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $this->authorize('view', $purchaseRequest);
        $purchaseRequest->load(['department', 'requester', 'ticket', 'partRequest', 'currentDept',
            'items.sparePart', 'approvals.approver', 'approvals.department']);

        return view('purchase-requests.show', ['pr' => $purchaseRequest]);
    }

    public function approve(Request $request, PurchaseRequest $purchaseRequest)
    {
        $this->authorize('decide', $purchaseRequest);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $this->procurement->approve($purchaseRequest, $request->user(), $data['note'] ?? null);

        return back()->with('success', 'تم اعتماد الطلب وتمريره للمرحلة التالية.');
    }

    public function reject(Request $request, PurchaseRequest $purchaseRequest)
    {
        $this->authorize('decide', $purchaseRequest);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->procurement->reject($purchaseRequest, $request->user(), $data['reason']);

        return back()->with('success', 'تم رفض الطلب.');
    }

    public function receive(Request $request, PurchaseRequest $purchaseRequest)
    {
        $this->authorize('receive', $purchaseRequest);
        $this->procurement->receive($purchaseRequest, $request->user());

        return back()->with('success', 'تم تنفيذ الطلب بنجاح.');
    }

    /** Printable A4 PDF (Arabic, via mPDF). */
    public function print(PurchaseRequest $purchaseRequest)
    {
        $this->authorize('view', $purchaseRequest);
        $purchaseRequest->load(['department', 'requester', 'ticket', 'company',
            'items.sparePart', 'approvals.approver', 'approvals.department']);

        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $tempDir,
            'default_font' => 'dejavusans', 'autoScriptToLang' => true, 'autoLangToFont' => true,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML(view('purchase-requests.pdf', ['pr' => $purchaseRequest])->render());

        return response($mpdf->Output($purchaseRequest->request_number . '.pdf', \Mpdf\Output\Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $purchaseRequest->request_number . '.pdf"',
        ]);
    }
}
