<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpareCategory extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'department_id', 'name', 'code',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function spareParts()
    {
        return $this->hasMany(SparePart::class, 'category_id');
    }

    public function isGlobal(): bool
    {
        return is_null($this->department_id);
    }

    /** Categories a department can use: its own + shared (global) categories. */
    public function scopeForDepartment($query, ?int $departmentId)
    {
        return $query->where(function ($q) use ($departmentId) {
            $q->whereNull('department_id');
            if ($departmentId) {
                $q->orWhere('department_id', $departmentId);
            }
        });
    }
}
