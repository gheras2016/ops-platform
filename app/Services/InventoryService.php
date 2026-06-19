<?php

namespace App\Services;

use App\Models\SparePart;

class InventoryService
{
    public function list()
    {
        return SparePart::with(['category'])->latest()->paginate(20);
    }

    public function adjustStock(SparePart $sparePart, int $quantity)
    {
        $sparePart->current_stock += $quantity;
        $sparePart->save();

        return $sparePart;
    }

    public function setStock(SparePart $sparePart, int $quantity)
    {
        $sparePart->current_stock = $quantity;
        $sparePart->save();

        return $sparePart;
    }
}
