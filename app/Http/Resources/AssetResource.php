<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'code'             => $this->code,
            'serial_number'    => $this->serial_number,
            'brand'            => $this->brand,
            'model'            => $this->model,
            'installation_date'=> $this->installation_date,
            'warranty_expiry'  => $this->warranty_expiry,

            'category'  => $this->category?->name,
            'location'  => $this->location?->name,
            'department'=> $this->department?->name,
        ];
    }
}
