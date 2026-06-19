<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'asset_code', 'name', 'category_id', 'location_id', 'department_id',
        'serial_number', 'brand', 'model', 'status', 'installation_date', 'warranty_expiry',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'warranty_expiry' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
