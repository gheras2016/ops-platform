<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'code', 'description', 'status',
    ];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
