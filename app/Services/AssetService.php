<?php

namespace App\Services;

use App\Models\Asset;

class AssetService
{
    public function list()
    {
        return Asset::with(['category', 'location', 'department'])->latest()->paginate(20);
    }

    public function create(array $data)
    {
        return Asset::create($data);
    }

    public function update(Asset $asset, array $data)
    {
        $asset->update($data);
        return $asset;
    }

    public function delete(Asset $asset)
    {
        return $asset->delete();
    }
}
