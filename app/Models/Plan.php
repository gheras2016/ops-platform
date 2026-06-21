<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_YEARLY = 'yearly';

    protected $fillable = [
        'name', 'slug', 'price', 'currency', 'billing_period',
        'duration_days', 'max_users', 'features', 'is_active', 'sort',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function periodLabel(): string
    {
        return $this->billing_period === self::PERIOD_MONTHLY ? 'شهري' : 'سنوي';
    }
}
