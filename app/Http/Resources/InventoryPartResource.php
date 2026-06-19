<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Read-only spare part for the mobile inventory module. `reserved` is the qty
 * held by active part requests; `available` = on-hand − reserved. The detail
 * view additionally embeds `recent_movements` (loaded stock transactions).
 *
 * @mixin \App\Models\SparePart
 */
class InventoryPartResource extends JsonResource
{
    public function toArray($request): array
    {
        $reserved = $this->reservedQty();
        $quantity = (int) $this->quantity;

        return [
            'id' => $this->id,
            'part_number' => $this->part_number,
            'name' => $this->name,
            'category' => $this->category?->name,
            'quantity' => $quantity,
            'reserved' => $reserved,
            'available' => max(0, $quantity - $reserved),
            'min_stock' => (int) $this->min_stock,
            'max_stock' => $this->max_stock !== null ? (int) $this->max_stock : null,
            'unit_price' => $this->unit_price !== null ? (float) $this->unit_price : null,
            'low_stock' => $this->isLowStock(),
            'out_of_stock' => $quantity <= 0,
            'has_open_request' => $reserved > 0,
            'recent_movements' => StockMovementResource::collection($this->whenLoaded('stockTransactions')),
        ];
    }
}
