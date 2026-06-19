<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One database notification, flattened for the mobile bell. The original
 * notification `data` payload (raised by TicketNotification) is spread to the
 * top level, plus the read flag and a short type name.
 *
 * @mixin \Illuminate\Notifications\DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = $this->data ?? [];

        return [
            'id' => $this->id,
            'type' => class_basename($this->type),
            'event' => $data['event'] ?? null,
            'message' => $data['message'] ?? '',
            'icon' => $data['icon'] ?? 'fa-bell',
            'color' => $data['color'] ?? 'gray',
            'actor' => $data['actor'] ?? null,
            'ticket_id' => $data['ticket_id'] ?? null,
            'ticket_number' => $data['ticket_number'] ?? null,
            'ticket_title' => $data['ticket_title'] ?? null,
            'is_read' => $this->read_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
