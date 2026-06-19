<?php

namespace App\Services;

use App\Models\SparePart;

class SparePartService
{
    public function list()
    {
        return SparePart::with(['category'])->latest()->paginate(20);
    }

    public function create(array $data)
    {
        return SparePart::create($data);
    }

    public function update(SparePart $sparePart, array $data)
    {
        $sparePart->update($data);
        return $sparePart;
    }

    public function delete(SparePart $sparePart)
    {
        return $sparePart->delete();
    }
}
