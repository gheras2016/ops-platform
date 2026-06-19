<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_request_id', 'spare_part_id', 'custom_name', 'part_request_item_id', 'quantity', 'unit_price',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class, 'spare_part_id');
    }

    public function partRequestItem()
    {
        return $this->belongsTo(PartRequestItem::class, 'part_request_item_id');
    }

    public function displayName(): string
    {
        return $this->sparePart?->name ?? ($this->custom_name ?: 'قطعة');
    }
}
