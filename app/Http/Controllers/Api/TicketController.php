<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\TicketDetailResource;
use App\Http\Resources\TicketResource;
use App\Models\Department;
use App\Models\Location;
use App\Models\Priority;
use App\Models\Ticket;
use App\Services\TicketWorkflowService;
use Illuminate\Http\Request;

/**
 * Mobile API for tickets. Shares the exact business logic of the web app via
 * TicketWorkflowService + TicketPolicy; only the transport (JSON) differs.
 */
class TicketController extends Controller
{
    public function __construct(protected TicketWorkflowService $workflow)
    {
    }

    /** Role-scoped, filterable, paginated list. */
    public function index(Request $request)
    {
        $user = $request->user();

        $tickets = Ticket::with(['department', 'priority', 'creator', 'technician', 'location'])
            ->visibleTo($user)
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$s}%")
                ->orWhere('ticket_number', 'like', "%{$s}%")))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->department, fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->priority, fn ($q, $v) => $q->where('priority_id', $v))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return TicketResource::collection($tickets);
    }

    /** Full detail + timeline + comments + permissions/actions. */
    public function show(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'department', 'priority', 'location', 'creator', 'technician', 'assigner', 'closer',
            'events.user', 'comments.user',
            'spareParts.creator', 'partRequests.items.sparePart', 'partRequests.requester',
        ]);

        return new TicketDetailResource($ticket);
    }

    /** Create (open) a ticket. */
    public function store(StoreTicketRequest $request)
    {
        $data = collect($request->validated())->except('files')->all();
        $ticket = $this->workflow->create($data, $request->user());

        $ticket->load(['department', 'priority', 'location', 'creator', 'events.user', 'comments.user']);

        return (new TicketDetailResource($ticket))->response()->setStatusCode(201);
    }

    /** Reference data for the "new ticket" form. */
    public function meta(Request $request)
    {
        $default = config('ticket_examples.default');

        return response()->json([
            'departments' => Department::where('is_active', true)
                ->where('accepts_tickets', true)->orderBy('name')->get(['id', 'name', 'type'])
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'type' => $d->type,
                    // Dynamic example hint for the new-ticket form, by department type.
                    'example' => config('ticket_examples.' . $d->type, $default),
                ]),
            'priorities' => Priority::orderBy('level')->get(['id', 'name', 'level', 'color']),
            'locations' => Location::orderBy('full_path')->get(['id', 'name', 'full_path']),
        ]);
    }

    /** Dashboard counts for the current user's visible tickets. */
    public function stats(Request $request)
    {
        $user = $request->user();

        $counts = Ticket::visibleTo($user)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byStatus = [];
        foreach (Ticket::STATUSES as $key => [$label, $color]) {
            $byStatus[] = [
                'status' => $key,
                'label' => $label,
                'color' => $color,
                'count' => (int) ($counts[$key] ?? 0),
            ];
        }

        $sumOf = fn (array $keys) => collect($keys)->sum(fn ($k) => (int) ($counts[$k] ?? 0));

        return response()->json([
            'total' => (int) $counts->sum(),
            'open' => $sumOf(Ticket::OPEN_STATUSES),
            'in_progress' => $sumOf([Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_PAUSED]),
            'awaiting_approval' => (int) ($counts[Ticket::STATUS_RESOLVED] ?? 0),
            'overdue' => Ticket::visibleTo($user)->overdue()->count(),
            'closed' => (int) ($counts[Ticket::STATUS_CLOSED] ?? 0),
            'by_status' => $byStatus,
        ]);
    }
}
