<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lean ticket shape for list views.
 */
class TicketResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->ticket_number,
            'title' => $this->title,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'status_color' => $this->statusColor(),
            'progress' => (int) $this->progress,
            'is_overdue' => $this->isOverdue(),
            'priority' => $this->priority ? [
                'id' => $this->priority->id,
                'name' => $this->priority->name,
                'level' => $this->priority->level,
                'color' => $this->priority->color,
            ] : null,
            'department' => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ] : null,
            'location_label' => $this->location?->full_path ?? $this->location?->name,
            'creator' => $this->creator ? ['id' => $this->creator->id, 'name' => $this->creator->name] : null,
            'technician' => $this->technician ? ['id' => $this->technician->id, 'name' => $this->technician->name] : null,
            'due_at' => $this->due_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
