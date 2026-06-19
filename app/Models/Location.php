<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'type', 'parent_id', 'full_path',
    ];

    public function parent()
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /** type => Arabic label. */
    public const TYPES = [
        'building' => 'مبنى',
        'floor' => 'دور',
        'room' => 'غرفة',
        'area' => 'منطقة',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** Build the "Parent / Child" path from the chosen parent + name. */
    public function computeFullPath(): string
    {
        $parent = $this->parent_id ? static::find($this->parent_id) : null;

        return $parent && $parent->full_path
            ? $parent->full_path . ' / ' . $this->name
            : $this->name;
    }
}
