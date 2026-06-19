<?php

namespace App\Services;

use App\Models\PurchaseRequest;

class PurchaseRequestService
{
    public function list()
    {
        return PurchaseRequest::with(['department', 'requester'])
            ->latest()
            ->paginate(20);
    }

    public function create(array $data)
    {
        return PurchaseRequest::create($data);
    }

    public function update(PurchaseRequest $request, array $data)
    {
        $request->update($data);
        return $request;
    }

    public function delete(PurchaseRequest $request)
    {
        return $request->delete();
    }
}
