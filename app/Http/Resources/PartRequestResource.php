<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * A spare-parts request raised against a ticket (catalogue or non-catalogue).
 * `available_actions` are the lifecycle actions the current user may take given
 * their role (PartRequestPolicy) and the request's state — the app renders the
 * approve / reject / issue / cancel buttons purely from this list.
 *
 * @mixin \App\Models\PartRequest
 */
class PartRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->request_number,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'status_color' => $this->statusColor(),
            'note' => $this->note,
            'requester' => $this->whenLoaded('requester', fn () => $this->requester?->name),
            'ticket' => $this->whenLoaded('ticket', fn () => $this->ticket ? [
                'id' => $this->ticket->id,
                'number' => $this->ticket->ticket_number,
                'title' => $this->ticket->title,
            ] : null),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'spare_part_id' => $item->spare_part_id,
                'name' => $item->displayName(),
                'description' => $item->description,
                'is_custom' => $item->isCustom(),
                'qty_requested' => (int) $item->qty_requested,
                'qty_approved' => (int) $item->qty_approved,
                'qty_issued' => (int) $item->qty_issued,
            ])),
            'available_actions' => $this->availableActions($request),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function availableActions($request): array
    {
        $gate = Gate::forUser($request->user());
        $actions = [];

        if ($this->canBeApproved() && $gate->allows('approve', $this->resource)) {
            $actions[] = 'approve';
            $actions[] = 'reject';
        }
        if ($this->canBeIssued() && $gate->allows('issue', $this->resource)) {
            $actions[] = 'issue';
        }
        if ($this->canBeCancelled() && $gate->allows('cancel', $this->resource)) {
            $actions[] = 'cancel';
        }

        return $actions;
    }
}
