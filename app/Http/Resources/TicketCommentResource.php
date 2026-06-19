<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketCommentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'is_internal' => (bool) $this->is_internal,
            'user' => $this->user ? ['id' => $this->user->id, 'name' => $this->user->name] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
