<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, BelongsToCompany, SoftDeletes;

    // Lifecycle statuses
    public const STATUS_OPEN = 'open';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    /** Status => [label, color] for the UI. */
    public const STATUSES = [
        self::STATUS_OPEN        => ['مفتوحة', 'gray'],
        self::STATUS_ASSIGNED    => ['تم الإسناد', 'indigo'],
        self::STATUS_ACCEPTED    => ['تم القبول', 'blue'],
        self::STATUS_IN_PROGRESS => ['قيد التنفيذ', 'amber'],
        self::STATUS_PAUSED      => ['متوقفة', 'orange'],
        self::STATUS_RESOLVED    => ['تم الحل', 'teal'],
        self::STATUS_CLOSED      => ['مغلقة', 'green'],
        self::STATUS_REJECTED    => ['مرفوضة', 'red'],
        self::STATUS_CANCELLED   => ['ملغاة', 'slate'],
    ];

    /** Statuses considered "active / open work". */
    public const OPEN_STATUSES = [
        self::STATUS_OPEN, self::STATUS_ASSIGNED, self::STATUS_ACCEPTED,
        self::STATUS_IN_PROGRESS, self::STATUS_PAUSED, self::STATUS_RESOLVED,
    ];

    protected $fillable = [
        'company_id', 'ticket_number', 'title', 'description',
        'department_id', 'location_id', 'location_detail', 'priority_id', 'asset_id', 'item_id',
        'status', 'progress', 'created_by', 'assigned_to', 'assigned_by', 'closed_by',
        'assigned_at', 'accepted_at', 'started_at', 'resolved_at', 'closed_at', 'due_at',
        'resolution_note', 'rejected_reason',
    ];

    protected $casts = [
        'progress' => 'integer',
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class)->latest();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->oldest();
    }

    public function pauseLogs(): HasMany
    {
        return $this->hasMany(TicketPauseLog::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function spareParts(): HasMany
    {
        return $this->hasMany(TicketSparePart::class);
    }

    public function partRequests(): HasMany
    {
        return $this->hasMany(PartRequest::class)->latest();
    }

    /** The currently-open pause log (paused but not yet resumed), if any. */
    public function activePause()
    {
        return $this->hasOne(TicketPauseLog::class)->whereNull('resumed_at')->latestOfMany();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_CANCELLED, self::STATUS_REJECTED]);
    }

    /**
     * Restrict a query to tickets the given user is allowed to see:
     *  - admins: everything in their tenant (scope already applies)
     *  - department head: tickets of departments they head
     *  - technician: tickets assigned to them
     *  - requester: tickets they created
     * Roles are additive (a head who also opens tickets sees both).
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $headDeptIds = $user->isDepartmentHead()
            ? $user->headedDepartments()->pluck('id')->all()
            : [];

        return $query->where(function ($q) use ($user, $headDeptIds) {
            $q->where('created_by', $user->id)
                ->orWhere('assigned_to', $user->id);

            if (! empty($headDeptIds)) {
                $q->orWhereIn('department_id', $headDeptIds);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Presentation / state helpers
    |--------------------------------------------------------------------------
    */
    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }

    public function isOverdue(): bool
    {
        return $this->due_at
            && $this->due_at->isPast()
            && ! in_array($this->status, [self::STATUS_CLOSED, self::STATUS_CANCELLED, self::STATUS_REJECTED]);
    }

    public function canBeAssigned(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_REJECTED, self::STATUS_ASSIGNED]);
    }

    public function canBeAccepted(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    public function canBeStarted(): bool
    {
        return in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_PAUSED]);
    }

    public function canBePaused(): bool
    {
        // A ticket can be paused once the technician has it in hand (accepted) or
        // is actively working on it — e.g. when blocked waiting on a spare part.
        return in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_IN_PROGRESS], true);
    }

    public function canBeResolved(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_CLOSED, self::STATUS_CANCELLED]);
    }

    /** Total cost of spare parts consumed on this ticket. */
    public function partsCost(): float
    {
        return (float) $this->spareParts->sum(fn ($p) => (int) $p->quantity_used * (float) ($p->unit_cost ?? 0));
    }

    /** Human-readable handling duration (start → resolve/close, else → now). */
    public function handlingDuration(): ?string
    {
        if (! $this->started_at) {
            return null;
        }
        $end = $this->resolved_at ?? $this->closed_at ?? now();

        return $this->started_at->diffForHumans($end, ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
    }
}
