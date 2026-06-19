<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One used spare part recorded on a ticket. `is_deducted` is true once the
 * quantity has actually left the warehouse (at ticket close, or via a warehouse
 * issue); pending lines can still be removed.
 *
 * @mixin \App\Models\TicketSparePart
 */
class TicketSparePartResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'spare_part_id' => $this->spare_part_id,
            'name' => $this->displayName(),
            'is_custom' => $this->isCustom(),
            'quantity_used' => (int) $this->quantity_used,
            'unit_cost' => $this->unit_cost !== null ? (float) $this->unit_cost : null,
            'line_total' => $this->lineTotal(),
            'is_deducted' => $this->isDeducted(),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
