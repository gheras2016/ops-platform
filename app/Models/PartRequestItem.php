<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartRequestItem extends Model
{
    protected $fillable = [
        'part_request_id', 'spare_part_id', 'custom_name', 'description',
        'qty_requested', 'qty_approved', 'qty_issued', 'qty_used', 'qty_returned', 'unit_cost',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

    public function partRequest(): BelongsTo
    {
        return $this->belongsTo(PartRequest::class);
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    /** Quantity still reserved (approved but not yet issued). */
    public function outstanding(): int
    {
        return max(0, (int) $this->qty_approved - (int) $this->qty_issued);
    }

    public function isCustom(): bool
    {
        return is_null($this->spare_part_id);
    }

    /** Catalog part name, or the typed custom name. */
    public function displayName(): string
    {
        return $this->sparePart?->name ?? ($this->custom_name ?: 'قطعة غير محددة');
    }

    /**
     * Per-item fulfilment status for the warehouse view: are we issuing from stock
     * or routing to purchase? Returns [label, color, icon].
     */
    public function fulfilmentStatus(): array
    {
        if ((int) $this->qty_approved <= 0) {
            return ['بانتظار الاعتماد', 'gray', 'fa-hourglass-half'];
        }
        if ((int) $this->qty_issued >= (int) $this->qty_approved) {
            return ['تم الصرف', 'green', 'fa-circle-check'];
        }
        if ($this->isCustom()) {
            return ['خارج الكاتالوج — مُحوّل للشراء', 'orange', 'fa-cart-arrow-down'];
        }

        // Catalog item: available to issue from on-hand stock, or routed to purchase.
        $onHand = (int) ($this->sparePart?->quantity ?? 0);
        if ($onHand >= $this->outstanding()) {
            return ['متوفر — جاهز للصرف', 'green', 'fa-box-open'];
        }

        return ['غير متوفر — مُحوّل للشراء', 'orange', 'fa-cart-arrow-down'];
    }
}

