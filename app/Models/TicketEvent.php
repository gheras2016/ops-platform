<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEvent extends Model
{
    protected $fillable = [
        'ticket_id', 'user_id', 'type', 'from_status', 'to_status', 'note', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /** type => [label, icon, color] for the timeline UI. */
    public const TYPES = [
        'created'   => ['تم إنشاء التذكرة', 'fa-circle-plus', 'gray'],
        'assigned'  => ['تم إسناد التذكرة لفني', 'fa-user-check', 'indigo'],
        'accepted'  => ['قبل الفني التذكرة', 'fa-handshake', 'blue'],
        'started'   => ['بدأ العمل', 'fa-play', 'amber'],
        'paused'    => ['تم إيقاف العمل مؤقتًا', 'fa-pause', 'orange'],
        'resumed'   => ['تم استئناف العمل', 'fa-play', 'amber'],
        'progress'  => ['تحديث نسبة الإنجاز', 'fa-bars-progress', 'blue'],
        'resolved'  => ['تم حل المشكلة', 'fa-flag-checkered', 'teal'],
        'approved'  => ['اعتمد رئيس القسم الإنجاز', 'fa-circle-check', 'green'],
        'rejected'  => ['أعاد رئيس القسم التذكرة', 'fa-rotate-left', 'red'],
        'commented' => ['تعليق جديد', 'fa-comment', 'slate'],
        'cancelled' => ['تم إلغاء التذكرة', 'fa-ban', 'slate'],
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }

    public function icon(): string
    {
        return self::TYPES[$this->type][1] ?? 'fa-circle';
    }

    public function color(): string
    {
        return self::TYPES[$this->type][2] ?? 'gray';
    }
}
