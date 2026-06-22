<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * A ticket attachment (photo or document) for the mobile client.
 *
 * @mixin \App\Models\TicketAttachment
 */
class TicketAttachmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'url' => Storage::disk('public')->url($this->path),
            'name' => $this->original_name,
            'mime' => $this->mime,
            'size' => $this->size,
            'is_image' => str_starts_with((string) $this->mime, 'image/'),
            'uploaded_by' => $this->whenLoaded('uploader', fn () => $this->uploader?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
