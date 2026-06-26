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
        // Use a batch-loaded value when present (avoids N+1 on list endpoints).
        if (! is_null($this->getAttribute('reserved_qty'))) {
            return (int) $this->getAttribute('reserved_qty');
        }

        return (int) PartRequestItem::where('spare_part_id', $this->id)
            ->whereHas('partRequest', fn ($q) => $q->whereIn('status', [PartRequest::STATUS_APPROVED, PartRequest::STATUS_PARTIAL]))
            ->selectRaw('COALESCE(SUM(qty_approved - qty_issued), 0) as r')
            ->value('r');
    }

    /** Reserved quantity for many parts in ONE query: [spare_part_id => reserved]. */
    public static function reservedMapFor(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return PartRequestItem::whereIn('spare_part_id', $ids)
            ->whereHas('partRequest', fn ($q) => $q->whereIn('status', [PartRequest::STATUS_APPROVED, PartRequest::STATUS_PARTIAL]))
            ->groupBy('spare_part_id')
            ->selectRaw('spare_part_id, COALESCE(SUM(qty_approved - qty_issued), 0) as r')
            ->pluck('r', 'spare_part_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /** Attach batch-loaded reserved quantities onto a collection of parts (no N+1). */
    public static function attachReserved($parts): void
    {
        $map = self::reservedMapFor($parts->pluck('id')->all());
        $parts->each(fn ($p) => $p->setAttribute('reserved_qty', $map[$p->id] ?? 0));
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

