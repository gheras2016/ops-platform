<?php

namespace App\Services;

use App\Models\AssetCategory;

class AssetCategoryService
{
    public function list()
    {
        return AssetCategory::latest()->paginate(20);
    }

    public function create(array $data)
    {
        return AssetCategory::create($data);
    }

    public function update(AssetCategory $category, array $data)
    {
        $category->update($data);
        return $category;
    }

    public function delete(AssetCategory $category)
    {
        return $category->delete();
    }
}
