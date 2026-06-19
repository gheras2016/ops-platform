<?php

namespace App\Services;

use App\Models\PurchaseRequestItem;

class PurchaseRequestItemService
{
    public function list()
    {
        return PurchaseRequestItem::with(['purchaseRequest', 'sparePart'])
            ->latest()
            ->paginate(20);
    }

    public function create(array $data)
    {
        return PurchaseRequestItem::create($data);
    }

    public function update(PurchaseRequestItem $item, array $data)
    {
        $item->update($data);
        return $item;
    }

    public function delete(PurchaseRequestItem $item)
    {
        return $item->delete();
    }
}
