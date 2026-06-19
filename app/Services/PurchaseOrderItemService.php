<?php

namespace App\Services;

use App\Models\PurchaseOrderItem;

class PurchaseOrderItemService
{
    public function list()
    {
        return PurchaseOrderItem::with(['purchaseOrder', 'sparePart'])
            ->latest()
            ->paginate(20);
    }

    public function create(array $data)
    {
        return PurchaseOrderItem::create($data);
    }

    public function update(PurchaseOrderItem $item, array $data)
    {
        $item->update($data);
        return $item;
    }

    public function delete(PurchaseOrderItem $item)
    {
        return $item->delete();
    }
}
