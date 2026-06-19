<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'spare_part_id', 'type', 'quantity',
        'related_ticket_id', 'related_purchase_order_id', 'created_by',
    ];

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class, 'spare_part_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'related_ticket_id');
    }
}
