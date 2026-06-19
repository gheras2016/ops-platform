<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketPauseLog extends Model
{
    protected $fillable = [
        'ticket_id', 'paused_by', 'reason_code', 'reason', 'paused_at', 'resumed_by', 'resumed_at',
    ];

    protected $casts = [
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
    ];

    /** Common pause reasons. */
    public const REASONS = [
        'spare_part' => 'بانتظار قطعة غيار',
        'approval' => 'بانتظار موافقة',
        'access' => 'تعذر الوصول للموقع',
        'external' => 'بانتظار جهة خارجية',
        'info' => 'بانتظار معلومات إضافية',
        'other' => 'سبب آخر',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function pausedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paused_by');
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason_code] ?? ($this->reason_code ?: 'غير محدد');
    }
}
