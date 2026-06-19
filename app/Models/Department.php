<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'parent_id',
        'head_id',
        'color',
        'description',
        'is_active',
        'accepts_tickets',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'accepts_tickets' => 'boolean',
    ];

    /** Available department types (task-based). */
    public const TYPES = [
        'it' => 'تقنية المعلومات',
        'maintenance' => 'الصيانة العامة',
        'mechanical' => 'الميكانيكا',
        'electrical' => 'الكهرباء',
        'hvac' => 'التكييف والتبريد',
        'plumbing' => 'السباكة',
        'civil' => 'الإنشاءات',
        'safety' => 'السلامة',
        'general' => 'عام',
    ];

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /** Departments from this one up to the root (excluding self), nearest first. */
    public function ancestors(): array
    {
        $chain = [];
        $node = $this->parent;
        $guard = 0;
        while ($node && $guard++ < 20) {
            $chain[] = $node;
            $node = $node->parent;
        }

        return $chain;
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function technicians(): HasMany
    {
        return $this->hasMany(User::class)->whereHas('roles', fn ($q) => $q->where('name', User::ROLE_TECHNICIAN));
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
