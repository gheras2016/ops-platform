<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A spare-parts request raised against a ticket (catalogue or non-catalogue).
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
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
