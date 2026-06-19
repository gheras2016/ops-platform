<?php

namespace App\Services;

use App\Models\StockTransaction;

class StockTransactionService
{
    public function list()
    {
        return StockTransaction::with(['sparePart', 'workOrder', 'purchaseOrder'])
            ->latest()
            ->paginate(20);
    }

    public function create(array $data)
    {
        return StockTransaction::create($data);
    }

    public function update(StockTransaction $transaction, array $data)
    {
        $transaction->update($data);
        return $transaction;
    }

    public function delete(StockTransaction $transaction)
    {
        return $transaction->delete();
    }
}
