<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One stock movement (in/out) for a spare part, linked to its source ticket or
 * purchase order when applicable.
 *
 * @mixin \App\Models\StockTransaction
 */
class StockMovementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type, // 'in' | 'out'
            'type_label' => $this->type === 'in' ? 'وارد' : 'صادر',
            'quantity' => (int) $this->quantity,
            'ticket' => $this->ticket ? [
                'id' => $this->ticket->id,
                'number' => $this->ticket->ticket_number,
            ] : null,
            'purchase_order_id' => $this->related_purchase_order_id,
            'by' => $this->whenLoaded('user', fn () => $this->user?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
