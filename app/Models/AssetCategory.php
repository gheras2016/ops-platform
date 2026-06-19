<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'code',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class, 'category_id');
    }
}
