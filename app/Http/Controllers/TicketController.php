<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Department;
use App\Models\Location;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(protected TicketWorkflowService $workflow)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $tickets = Ticket::with(['department', 'priority', 'creator', 'technician', 'location'])
            ->visibleTo($user)
            ->when($request->search, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('title', 'like', "%{$s}%")
                    ->orWhere('ticket_number', 'like', "%{$s}%");
            }))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->department, fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->priority, fn ($q, $v) => $q->where('priority_id', $v))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'stats' => $this->statusCounts($user),
            'departments' => Department::orderBy('name')->get(),
            'priorities' => Priority::orderBy('level')->get(),
            'statuses' => Ticket::STATUSES,
        ]);
    }

    /** Kanban board grouping the user's visible tickets by lifecycle column. */
    public function board(Request $request)
    {
        $user = $request->user();

        $columns = [
            Ticket::STATUS_OPEN,
            Ticket::STATUS_ASSIGNED,
            Ticket::STATUS_ACCEPTED,
            Ticket::STATUS_IN_PROGRESS,
            Ticket::STATUS_PAUSED,
            Ticket::STATUS_RESOLVED,
        ];

        $tickets = Ticket::with(['department', 'priority', 'creator', 'technician'])
            ->visibleTo($user)
            ->whereIn('status', $columns)
            ->when($request->department, fn ($q, $v) => $q->where('department_id', $v))
            ->latest()
            ->get()
            ->groupBy('status');

        return view('tickets.board', [
            'columns' => $columns,
            'grouped' => $tickets,
            'departments' => $this->visibleDepartments($user),
        ]);
    }

    /** Departments relevant to the user (for filters): admin=all, head=headed, others=own. */
    protected function visibleDepartments(User $user)
    {
        if ($user->isAdmin()) {
            return Department::orderBy('name')->get();
        }
        if ($user->isDepartmentHead()) {
            return $user->headedDepartments()->orderBy('name')->get();
        }

        return $user->department_id
            ? Department::whereKey($user->department_id)->get()
            : collect();
    }

    public function create()
    {
        $this->authorize('create', Ticket::class);

        return view('tickets.create', [
            // Only operational departments receive tickets (management divisions are approval-only).
            'departments' => Department::where('is_active', true)->where('accepts_tickets', true)->orderBy('name')->get(),
            'priorities' => Priority::orderBy('level')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    public function store(StoreTicketRequest $request)
    {
        $data = collect($request->validated())->except('files')->all();
        $ticket = $this->workflow->create($data, $request->user());

        foreach ($request->file('files', []) as $file) {
            $path = $file->store("tickets/{$ticket->id}", 'public');
            $ticket->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'تم إنشاء التذكرة رقم ' . $ticket->ticket_number . ' بنجاح');
    }

    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'department.head', 'priority', 'location', 'asset',
            'creator', 'technician', 'assigner', 'closer',
            'events.user', 'comments.user', 'pauseLogs.pausedBy',
            'attachments.uploader', 'spareParts.sparePart',
            'partRequests.items.sparePart', 'partRequests.requester', 'partRequests.approver', 'partRequests.issuer',
            'partRequests.purchaseRequest',
        ]);

        // Technicians the head can assign to (department members with technician role).
        $technicians = collect();
        if (auth()->user()->can('assign', $ticket) && $ticket->department_id) {
            $technicians = User::role(User::ROLE_TECHNICIAN)
                ->where('department_id', $ticket->department_id)
                ->orderBy('name')
                ->get();
        }

        // Spare parts for the resolve dialog. Technicians only see parts relevant
        // to this ticket's department (+ shared/global); inventory managers see all.
        $spareParts = collect();
        if (auth()->user()->can('work', $ticket)) {
            $query = \App\Models\SparePart::with('category.department')->orderBy('name');
            if (! auth()->user()->canManageInventory()) {
                $query->forDepartment($ticket->department_id);
            }
            $spareParts = $query->get(['id', 'name', 'part_number', 'quantity', 'category_id']);
        }

        $priorities = Priority::orderBy('level')->get();

        return view('tickets.show', compact('ticket', 'technicians', 'spareParts', 'priorities'));
    }

    /** Professional A4 service report (PDF) for a ticket. */
    public function report(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'company', 'department.head', 'priority', 'location', 'asset', 'creator', 'technician', 'closer',
            'events.user', 'pauseLogs.pausedBy', 'spareParts.sparePart',
        ]);

        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $tempDir,
            'default_font' => 'dejavusans', 'autoScriptToLang' => true, 'autoLangToFont' => true,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML(view('tickets.report', compact('ticket'))->render());

        return response($mpdf->Output($ticket->ticket_number . '.pdf', \Mpdf\Output\Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $ticket->ticket_number . '.pdf"',
        ]);
    }

    public function edit(Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        return view('tickets.edit', [
            'ticket' => $ticket,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'priorities' => Priority::orderBy('level')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        $ticket->update($request->validated());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'تم تحديث التذكرة بنجاح');
    }

    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);
        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', 'تم حذف التذكرة');
    }

    /** Status -> count for the current user's visible tickets. */
    protected function statusCounts(User $user): array
    {
        $counts = Ticket::visibleTo($user)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $out = ['all' => $counts->sum()];
        foreach (array_keys(Ticket::STATUSES) as $status) {
            $out[$status] = (int) ($counts[$status] ?? 0);
        }

        return $out;
    }
}
