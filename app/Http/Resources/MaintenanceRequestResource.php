<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'priority_id'   => $this->priority_id,
            'status_id'     => $this->status_id,
            'asset_id'      => $this->asset_id,
            'user_id'       => $this->user_id,

            'priority'      => new PriorityResource($this->whenLoaded('priority')),
            'status'        => new TicketStatusResource($this->whenLoaded('status')),
            'asset'         => new AssetResource($this->whenLoaded('asset')),
            'user'          => new UserResource($this->whenLoaded('user')),

            'created_at'    => $this->created_at?->format('Y-m-d H:i'),
            'updated_at'    => $this->updated_at?->format('Y-m-d H:i'),
        ];
    }
}
