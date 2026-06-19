<?php

namespace App\Http\Resources;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * Full ticket detail for the mobile detail screen: core fields + timeline +
 * comments + the current user's permissions and the actions available given
 * the ticket's state. The app renders buttons purely from `available_actions`.
 */
class TicketDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();
        $gate = Gate::forUser($user);

        $perms = [
            'assign' => $gate->allows('assign', $this->resource),
            'work' => $gate->allows('work', $this->resource),
            'approve' => $gate->allows('approve', $this->resource),
            'comment' => $gate->allows('comment', $this->resource),
            'cancel' => $gate->allows('cancel', $this->resource),
            'update' => $gate->allows('update', $this->resource),
        ];

        return [
            'id' => $this->id,
            'number' => $this->ticket_number,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'status_color' => $this->statusColor(),
            'progress' => (int) $this->progress,
            'is_overdue' => $this->isOverdue(),
            'resolution_note' => $this->resolution_note,
            'rejected_reason' => $this->rejected_reason,
            'location_label' => $this->location?->full_path ?? $this->location?->name,
            'location_detail' => $this->location_detail,

            'priority' => $this->priority ? [
                'id' => $this->priority->id,
                'name' => $this->priority->name,
                'level' => $this->priority->level,
                'color' => $this->priority->color,
            ] : null,
            'department' => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ] : null,
            // The ticket's "category" for contextual help is its department type.
            'category' => $this->department?->type,
            'category_label' => $this->department?->typeLabel(),
            'creator' => $this->ref($this->creator),
            'technician' => $this->ref($this->technician),
            'assigner' => $this->ref($this->assigner),
            'closer' => $this->ref($this->closer),

            'due_at' => $this->due_at?->toIso8601String(),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),

            'events' => TicketEventResource::collection($this->whenLoaded('events')),
            'comments' => TicketCommentResource::collection($this->whenLoaded('comments')),
            'spare_parts' => TicketSparePartResource::collection($this->whenLoaded('spareParts')),
            'part_requests' => PartRequestResource::collection($this->whenLoaded('partRequests')),
            'parts_cost' => $this->whenLoaded('spareParts', fn () => $this->partsCost()),

            'permissions' => $perms,
            'can_manage_parts' => $perms['work']
                && ! in_array($this->status, [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED], true),
            'available_actions' => $this->availableActions($perms),
            // Department technicians the head can assign to (only when relevant).
            'assignable_technicians' => $this->when(
                $perms['assign'] && $this->canBeAssigned() && $this->department_id,
                fn () => User::role(User::ROLE_TECHNICIAN)
                    ->where('department_id', $this->department_id)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
                    ->values()
            ),
        ];
    }

    private function ref($user): ?array
    {
        return $user ? ['id' => $user->id, 'name' => $user->name] : null;
    }

    /** The lifecycle actions the current user may take right now. */
    private function availableActions(array $perms): array
    {
        $actions = [];

        if ($perms['assign'] && $this->canBeAssigned()) {
            $actions[] = 'assign';
        }
        if ($perms['work']) {
            if ($this->canBeAccepted()) $actions[] = 'accept';
            if ($this->status === Ticket::STATUS_ACCEPTED) $actions[] = 'start';
            if ($this->status === Ticket::STATUS_PAUSED) $actions[] = 'resume';
            if ($this->canBePaused()) $actions[] = 'pause';
            if ($this->status === Ticket::STATUS_IN_PROGRESS) $actions[] = 'progress';
            if ($this->canBeResolved()) $actions[] = 'resolve';
        }
        if ($perms['approve'] && $this->canBeApproved()) {
            $actions[] = 'approve';
            $actions[] = 'reject';
        }
        if ($perms['cancel'] && ! $this->isClosed() && $this->status !== Ticket::STATUS_RESOLVED) {
            $actions[] = 'cancel';
        }

        return $actions;
    }
}
