<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Company extends Model
{
    use HasFactory;

    public const SUB_TRIAL = 'trial';
    public const SUB_ACTIVE = 'active';
    public const SUB_GRACE = 'grace';
    public const SUB_SUSPENDED = 'suspended';
    public const SUB_CANCELLED = 'cancelled';

    public const SUB_STATUSES = [
        self::SUB_TRIAL => ['تجربة مجانية', 'blue'],
        self::SUB_ACTIVE => ['اشتراك نشط', 'green'],
        self::SUB_GRACE => ['مهلة سماح', 'amber'],
        self::SUB_SUSPENDED => ['موقوف', 'red'],
        self::SUB_CANCELLED => ['ملغى', 'gray'],
    ];

    protected $fillable = [
        'name',
        'code',
        'logo',
        'email',
        'phone',
        'address',
        'primary_color',
        'sidebar_color',
        'bg_color',
        'is_active',
        'subscription_status',
        'plan_id',
        'trial_ends_at',
        'current_period_end',
        'grace_days',
    ];

    public function logoUrl(): ?string
    {
        return $this->logo ? \Illuminate\Support\Facades\Storage::url($this->logo) : null;
    }

    protected $casts = [
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'current_period_end' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Subscription helpers
    |--------------------------------------------------------------------------
    */

    public function isOnTrial(): bool
    {
        return $this->subscription_status === self::SUB_TRIAL;
    }

    public function isSuspended(): bool
    {
        return $this->subscription_status === self::SUB_SUSPENDED;
    }

    /** The active deadline: the paid period end, else the trial end. Null = no expiry. */
    public function subscriptionEndsAt(): ?Carbon
    {
        return $this->current_period_end ?? $this->trial_ends_at;
    }

    /** Whole days until the deadline (negative if past). Null when there is no deadline. */
    public function daysRemaining(): ?int
    {
        $end = $this->subscriptionEndsAt();

        return $end ? (int) now()->startOfDay()->diffInDays($end->copy()->startOfDay(), false) : null;
    }

    public function isExpired(): bool
    {
        $end = $this->subscriptionEndsAt();

        return $end !== null && $end->isPast();
    }

    public function subStatusLabel(): string
    {
        return self::SUB_STATUSES[$this->subscription_status][0] ?? $this->subscription_status ?? '—';
    }

    public function subStatusColor(): string
    {
        return self::SUB_STATUSES[$this->subscription_status][1] ?? 'gray';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
