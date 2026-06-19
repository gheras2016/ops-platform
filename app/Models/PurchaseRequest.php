<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory, BelongsToCompany;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_DEPT = 'pending_dept';
    public const STATUS_PENDING_FINANCE = 'pending_finance';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RECEIVED = 'received';

    public const STATUSES = [
        self::STATUS_DRAFT           => ['مسودة', 'gray'],
        self::STATUS_PENDING_DEPT    => ['بانتظار اعتماد الإدارة', 'amber'],
        self::STATUS_PENDING_FINANCE => ['بانتظار اعتماد المالية', 'orange'],
        self::STATUS_APPROVED        => ['معتمد - بانتظار التنفيذ', 'indigo'],
        self::STATUS_REJECTED        => ['مرفوض', 'red'],
        self::STATUS_RECEIVED        => ['تم التنفيذ', 'green'],
    ];

    public const TYPE_STOCK = 'stock';
    public const TYPE_DIRECT = 'direct';
    public const TYPES = [
        self::TYPE_STOCK  => 'توريد للمخزون',
        self::TYPE_DIRECT => 'شراء مباشر عاجل',
    ];

    protected $fillable = [
        'company_id', 'request_number', 'requested_by', 'department_id',
        'ticket_id', 'part_request_id', 'status', 'fulfillment_type', 'current_dept_id',
        'justification', 'supplier', 'notes',
        'approved_by', 'approved_at', 'rejected_reason', 'received_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function partRequest()
    {
        return $this->belongsTo(PartRequest::class, 'part_request_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseRequestItem::class, 'purchase_request_id');
    }

    public function currentDept()
    {
        return $this->belongsTo(Department::class, 'current_dept_id');
    }

    public function approvals()
    {
        return $this->hasMany(PurchaseApproval::class)->orderBy('id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'purchase_request_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->fulfillment_type] ?? $this->fulfillment_type;
    }

    public function isDirect(): bool
    {
        return $this->fulfillment_type === self::TYPE_DIRECT;
    }

    public function totalEstimate(): float
    {
        return (float) $this->items->sum(fn ($i) => (int) $i->quantity * (float) ($i->unit_price ?? 0));
    }

    public function canDeptDecide(): bool
    {
        return $this->status === self::STATUS_PENDING_DEPT;
    }

    public function canFinanceDecide(): bool
    {
        return $this->status === self::STATUS_PENDING_FINANCE;
    }

    public function canBeReceived(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_DEPT, self::STATUS_PENDING_FINANCE, self::STATUS_APPROVED]);
    }
}
