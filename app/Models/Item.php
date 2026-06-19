<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'code', 'category_id', 'unit', 'location', 'quantity', 'price', 'status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
