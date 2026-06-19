<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One entry in a ticket's audit timeline.
 */
class TicketEventResource extends JsonResource
{
    /** event type => [Arabic label, icon keyword for the mobile UI]. */
    private const META = [
        'created' => ['أُنشئ البلاغ', 'create'],
        'assigned' => ['تم الإسناد', 'assign'],
        'accepted' => ['تم القبول', 'accept'],
        'started' => ['بدأ العمل', 'start'],
        'resumed' => ['استئناف العمل', 'resume'],
        'paused' => ['إيقاف مؤقت', 'pause'],
        'progress' => ['تحديث نسبة الإنجاز', 'progress'],
        'resolved' => ['تم الإنجاز', 'resolve'],
        'approved' => ['اعتماد وإغلاق', 'approve'],
        'rejected' => ['أُعيد للفني', 'reject'],
        'cancelled' => ['أُلغي البلاغ', 'cancel'],
        'commented' => ['تعليق', 'comment'],
    ];

    public function toArray($request): array
    {
        [$label, $icon] = self::META[$this->type] ?? [$this->type, 'event'];

        return [
            'id' => $this->id,
            'type' => $this->type,
            'label' => $label,
            'icon' => $icon,
            'note' => $this->note,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'meta' => $this->meta,
            'user' => $this->user ? ['id' => $this->user->id, 'name' => $this->user->name] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
