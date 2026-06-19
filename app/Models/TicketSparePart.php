<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketSparePart extends Model
{
    protected $fillable = [
        'ticket_id', 'spare_part_id', 'custom_name', 'quantity_used', 'unit_cost', 'deducted_at', 'created_by',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'deducted_at' => 'datetime',
    ];

    /** True when this used part is not in the catalogue. */
    public function isCustom(): bool
    {
        return is_null($this->spare_part_id);
    }

    /** True once the quantity has been drawn from stock. */
    public function isDeducted(): bool
    {
        return ! is_null($this->deducted_at);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Display name: catalogue name, else the custom name. */
    public function displayName(): string
    {
        return $this->sparePart?->name ?? $this->custom_name ?? '—';
    }

    public function lineTotal(): float
    {
        return (float) $this->quantity_used * (float) ($this->unit_cost ?? 0);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }
}
