<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

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
    ];

    public function logoUrl(): ?string
    {
        return $this->logo ? \Illuminate\Support\Facades\Storage::url($this->logo) : null;
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

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
