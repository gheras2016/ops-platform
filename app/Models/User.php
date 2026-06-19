<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    // Role constants
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_COMPANY_ADMIN = 'company_admin';
    public const ROLE_DEPARTMENT_HEAD = 'department_head';
    public const ROLE_TECHNICIAN = 'technician';
    public const ROLE_REQUESTER = 'requester';
    public const ROLE_WAREHOUSE_MANAGER = 'warehouse_manager';
    public const ROLE_FINANCE_MANAGER = 'finance_manager';

    protected $fillable = [
        'company_id',
        'department_id',
        'location_id',
        'name',
        'email',
        'phone',
        'job_title',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** Departments this user is head of. */
    public function headedDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'head_id');
    }

    /**
     * Restrict a user query to the acting user's company.
     * (User has no global CompanyScope to avoid auth-resolution recursion.)
     */
    public function scopeTenantScoped($query, ?User $actor)
    {
        if ($actor && ! $actor->isSuperAdmin() && $actor->company_id) {
            $query->where('company_id', $actor->company_id);
        }

        return $query;
    }

    /** Tickets this user opened (requester). */
    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    /** Tickets assigned to this user (technician). */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    /*
    |--------------------------------------------------------------------------
    | Role helpers
    |--------------------------------------------------------------------------
    */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function isCompanyAdmin(): bool
    {
        return $this->hasRole(self::ROLE_COMPANY_ADMIN);
    }

    public function isDepartmentHead(): bool
    {
        return $this->hasRole(self::ROLE_DEPARTMENT_HEAD);
    }

    public function isTechnician(): bool
    {
        return $this->hasRole(self::ROLE_TECHNICIAN);
    }

    public function isRequester(): bool
    {
        return $this->hasRole(self::ROLE_REQUESTER);
    }

    public function isWarehouseManager(): bool
    {
        return $this->hasRole(self::ROLE_WAREHOUSE_MANAGER);
    }

    /** Can manage the inventory/spare-parts catalogue org-wide. */
    public function canManageInventory(): bool
    {
        return $this->isAdmin() || $this->isWarehouseManager();
    }

    public function isFinanceManager(): bool
    {
        return $this->hasRole(self::ROLE_FINANCE_MANAGER);
    }

    /** Can approve procurement (finance) — finance manager or admin. */
    public function canApprovePurchasing(): bool
    {
        return $this->isAdmin() || $this->isFinanceManager();
    }

    /** Admin-level access (platform or company). */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole([self::ROLE_SUPER_ADMIN, self::ROLE_COMPANY_ADMIN]);
    }

    /** Can manage/approve tickets for a given department. */
    public function managesDepartment(?int $departmentId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->isDepartmentHead()
            && $departmentId
            && $this->headedDepartments()->where('id', $departmentId)->exists();
    }

    /**
     * Count of purchase requests awaiting THIS user's action — drives the sidebar
     * badge so a pending approval (e.g. an auto-routed out-of-catalogue purchase
     * sitting at the operations-manager level) is never missed.
     */
    public function actionablePurchaseCount(): int
    {
        $headed = $this->headedDepartments()->pluck('id')->all();

        if (! $this->isAdmin() && empty($headed) && ! $this->canApprovePurchasing() && ! $this->canManageInventory()) {
            return 0;
        }

        return \App\Models\PurchaseRequest::where(function ($q) use ($headed) {
            if (! empty($headed)) {
                $q->orWhere(fn ($w) => $w
                    ->where('status', \App\Models\PurchaseRequest::STATUS_PENDING_DEPT)
                    ->whereIn('current_dept_id', $headed));
            }
            if ($this->canApprovePurchasing()) {
                $q->orWhere('status', \App\Models\PurchaseRequest::STATUS_PENDING_FINANCE);
            }
            if ($this->canManageInventory()) {
                $q->orWhere('status', \App\Models\PurchaseRequest::STATUS_APPROVED);
            }
        })->count();
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $second = mb_substr($parts[1] ?? '', 0, 1);

        return mb_strtoupper($first . $second) ?: 'U';
    }
}
