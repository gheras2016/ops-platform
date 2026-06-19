<?php

namespace App\Services;

use App\Models\PurchaseOrder;

class PurchaseOrderService
{
    public function list()
    {
        return PurchaseOrder::with(['supplier', 'request'])
            ->latest()
            ->paginate(20);
    }

    public function create(array $data)
    {
        return PurchaseOrder::create($data);
    }

    public function update(PurchaseOrder $order, array $data)
    {
        $order->update($data);
        return $order;
    }

    public function delete(PurchaseOrder $order)
    {
        return $order->delete();
    }
}
