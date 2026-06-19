<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartRequest extends Model
{
    use HasFactory, BelongsToCompany;

    public const STATUS_PENDING = 'pending';            // awaiting head approval
    public const STATUS_APPROVED = 'approved';          // awaiting warehouse issue
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ISSUED = 'issued';              // fully issued
    public const STATUS_PARTIAL = 'partially_issued';   // some issued, shortage remains
    public const STATUS_PROCUREMENT = 'awaiting_procurement'; // converted to a purchase
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING     => ['بانتظار الاعتماد', 'amber'],
        self::STATUS_APPROVED    => ['معتمد - بانتظار الصرف', 'indigo'],
        self::STATUS_REJECTED    => ['مرفوض', 'red'],
        self::STATUS_ISSUED      => ['تم الصرف', 'green'],
        self::STATUS_PARTIAL     => ['صرف جزئي', 'orange'],
        self::STATUS_PROCUREMENT => ['بانتظار التوريد/الشراء', 'blue'],
        self::STATUS_CANCELLED   => ['ملغي', 'slate'],
    ];

    protected $fillable = [
        'company_id', 'request_number', 'ticket_id', 'department_id', 'requested_by',
        'status', 'note', 'approved_by', 'approved_at', 'rejected_reason', 'issued_by', 'issued_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PartRequestItem::class);
    }

    public function purchaseRequest()
    {
        return $this->hasOne(PurchaseRequest::class, 'part_request_id')->latest();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeIssued(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PARTIAL]);
    }

    /** Warehouse may push a shortage/custom line to procurement. */
    public function canBeConverted(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PARTIAL]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_PARTIAL]);
    }

    /** Active requests still hold a reservation on stock. */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PARTIAL]);
    }
}
