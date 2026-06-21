<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * A purchase request with its multi-stage approval chain. `available_actions`
 * are computed from PurchaseRequestPolicy + the current stage so the app renders
 * approve / reject / receive buttons without deciding anything itself.
 *
 * @mixin \App\Models\PurchaseRequest
 */
class PurchaseRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->request_number,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'status_color' => $this->statusColor(),
            'type' => $this->fulfillment_type,
            'type_label' => $this->typeLabel(),
            'is_direct' => $this->isDirect(),
            'justification' => $this->justification,
            'supplier' => $this->supplier,
            'notes' => $this->notes,
            'rejected_reason' => $this->rejected_reason,

            'department' => $this->whenLoaded('department', fn () => $this->department?->name),
            'current_dept' => $this->whenLoaded('currentDept', fn () => $this->currentDept?->name),
            'requester' => $this->whenLoaded('requester', fn () => $this->requester?->name),
            'ticket' => $this->whenLoaded('ticket', fn () => $this->ticket ? [
                'id' => $this->ticket->id,
                'number' => $this->ticket->ticket_number,
            ] : null),

            'total_estimate' => $this->whenLoaded('items', fn () => $this->totalEstimate()),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'spare_part_id' => $item->spare_part_id,
                'name' => $item->displayName(),
                'quantity' => (int) $item->quantity,
                'unit_price' => $item->unit_price !== null ? (float) $item->unit_price : null,
            ])),
            'approvals' => $this->whenLoaded('approvals', fn () => $this->approvals->map(fn ($a) => [
                'stage_label' => $a->stageLabel(),
                'decision' => $a->decision,
                'decision_label' => $a->decisionLabel(),
                'approver' => $a->approver?->name,
                'note' => $a->note,
                'decided_at' => $a->decided_at?->toIso8601String(),
            ])),

            'available_actions' => $this->availableActions($request),
            'created_at' => $this->created_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'received_at' => $this->received_at?->toIso8601String(),
        ];
    }

    private function availableActions($request): array
    {
        $gate = Gate::forUser($request->user());
        $actions = [];

        if (($this->canDeptDecide() || $this->canFinanceDecide()) && $gate->allows('decide', $this->resource)) {
            $actions[] = 'approve';
            $actions[] = 'reject';
        }
        if ($this->canBeReceived() && $gate->allows('receive', $this->resource)) {
            $actions[] = 'receive';
        }

        return $actions;
    }
}
