<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketDetailResource;
use App\Models\Ticket;
use App\Models\TicketPauseLog;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Illuminate\Http\Request;

/**
 * Ticket lifecycle transitions for the mobile API. Every method authorizes via
 * TicketPolicy, delegates to TicketWorkflowService, and returns the refreshed
 * detail resource (so the app re-renders status + available actions).
 */
class TicketActionController extends Controller
{
    public function __construct(protected TicketWorkflowService $workflow)
    {
    }

    public function assign(Request $request, Ticket $ticket)
    {
        $this->authorize('assign', $ticket);

        $data = $request->validate([
            'technician_id' => ['required', 'exists:users,id'],
            'priority_id' => ['nullable', 'exists:priorities,id'],
            'note' => ['nullable', 'string', 'max:1000'],
            'due_at' => ['nullable', 'date'],
        ]);

        $technician = User::findOrFail($data['technician_id']);
        abort_unless($technician->department_id === $ticket->department_id, 422, 'الفني ليس ضمن هذا القسم.');

        $ticket->update(array_filter([
            'priority_id' => $data['priority_id'] ?? null,
            'due_at' => $data['due_at'] ?? null,
        ]));

        $this->workflow->assign($ticket, $technician, $request->user(), $data['note'] ?? null);

        return $this->respond($ticket);
    }

    public function accept(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);
        $this->workflow->accept($ticket, $request->user());

        return $this->respond($ticket);
    }

    public function start(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);
        $this->workflow->start($ticket, $request->user());

        return $this->respond($ticket);
    }

    public function pause(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);

        $data = $request->validate([
            'reason_code' => ['required', 'in:' . implode(',', array_keys(TicketPauseLog::REASONS))],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->workflow->pause($ticket, $request->user(), $data['reason_code'], $data['reason'] ?? null);

        return $this->respond($ticket);
    }

    public function resume(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);
        $this->workflow->resume($ticket, $request->user());

        return $this->respond($ticket);
    }

    public function progress(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);

        $data = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $this->workflow->setProgress($ticket, $request->user(), $data['progress']);

        return $this->respond($ticket);
    }

    public function resolve(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);

        $data = $request->validate([
            'resolution_note' => ['nullable', 'string', 'max:2000'],
            'parts' => ['nullable', 'array'],
            'parts.*.spare_part_id' => ['nullable', 'exists:spare_parts,id'],
            'parts.*.custom_name' => ['nullable', 'string', 'max:255'],
            'parts.*.quantity' => ['nullable', 'integer', 'min:1'],
            'parts.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->workflow->resolve($ticket, $request->user(), $data['resolution_note'] ?? null, $data['parts'] ?? []);

        return $this->respond($ticket);
    }

    public function approve(Request $request, Ticket $ticket)
    {
        $this->authorize('approve', $ticket);

        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $this->workflow->approve($ticket, $request->user(), $data['note'] ?? null);

        return $this->respond($ticket);
    }

    public function reject(Request $request, Ticket $ticket)
    {
        $this->authorize('approve', $ticket);

        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->workflow->reject($ticket, $request->user(), $data['reason']);

        return $this->respond($ticket);
    }

    public function cancel(Request $request, Ticket $ticket)
    {
        $this->authorize('cancel', $ticket);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $this->workflow->cancel($ticket, $request->user(), $data['reason'] ?? null);

        return $this->respond($ticket);
    }

    public function comment(Request $request, Ticket $ticket)
    {
        $this->authorize('comment', $ticket);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $this->workflow->comment($ticket, $request->user(), $data['body'], (bool) ($data['is_internal'] ?? false));

        return $this->respond($ticket);
    }

    /** Reload the ticket with everything the detail screen needs and return it. */
    private function respond(Ticket $ticket)
    {
        $ticket->refresh()->load([
            'department', 'priority', 'location', 'creator', 'technician', 'assigner', 'closer',
            'events.user', 'comments.user',
            'spareParts.creator', 'partRequests.items.sparePart', 'partRequests.requester',
        ]);

        return new TicketDetailResource($ticket);
    }
}
