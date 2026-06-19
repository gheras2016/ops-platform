<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A catalogue spare part for the in-ticket picker. `available` is free-to-promise
 * stock (on-hand minus quantities reserved by active part requests).
 *
 * @mixin \App\Models\SparePart
 */
class SparePartResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'part_number' => $this->part_number,
            'name' => $this->name,
            'category' => $this->category?->name,
            'quantity' => (int) $this->quantity,
            'available' => $this->availableQty(),
            'unit_price' => (float) $this->unit_price,
            'low_stock' => $this->isLowStock(),
        ];
    }
}
