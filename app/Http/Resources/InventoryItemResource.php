<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'sku'           => $this->sku,
            'category'      => $this->category?->name,
            'quantity'      => $this->quantity,
            'min_quantity'  => $this->min_quantity,
            'location'      => $this->location?->name,
            'created_at'    => $this->created_at,
        ];
    }
}
