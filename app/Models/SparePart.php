<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SparePart extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'part_number', 'name', 'category_id',
        'quantity', 'min_stock', 'max_stock', 'unit_price',
    ];

    public function category()
    {
        return $this->belongsTo(SpareCategory::class, 'category_id');
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'spare_part_id');
    }

    public function requestItems()
    {
        return $this->hasMany(PartRequestItem::class);
    }

    /** Quantity reserved by active (approved / partially-issued) part requests. */
    public function reservedQty(): int
    {
        return (int) PartRequestItem::where('spare_part_id', $this->id)
            ->whereHas('partRequest', fn ($q) => $q->whereIn('status', [PartRequest::STATUS_APPROVED, PartRequest::STATUS_PARTIAL]))
            ->selectRaw('COALESCE(SUM(qty_approved - qty_issued), 0) as r')
            ->value('r');
    }

    /** Free-to-promise stock = on-hand − reserved. */
    public function availableQty(): int
    {
        return max(0, (int) $this->quantity - $this->reservedQty());
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_stock;
    }

    /**
     * Parts relevant to a department: those whose category belongs to that
     * department, plus shared/global parts (global category, or no category).
     */
    public function scopeForDepartment($query, ?int $departmentId)
    {
        return $query->where(function ($w) use ($departmentId) {
            $w->whereHas('category', function ($c) use ($departmentId) {
                $c->whereNull('department_id');
                if ($departmentId) {
                    $c->orWhere('department_id', $departmentId);
                }
            })->orWhereDoesntHave('category');
        });
    }
}

