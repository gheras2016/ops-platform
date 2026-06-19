<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'contact_name'  => $this->contact_name,
            'phone'         => $this->phone,
            'email'         => $this->email,
            'address'       => $this->address,
            'created_at'    => $this->created_at?->format('Y-m-d H:i'),
            'updated_at'    => $this->updated_at?->format('Y-m-d H:i'),
        ];
    }
}
