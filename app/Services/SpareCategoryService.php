<?php

namespace App\Services;

use App\Models\SpareCategory;

class SpareCategoryService
{
    public function list()
    {
        return SpareCategory::latest()->paginate(20);
    }

    public function create(array $data)
    {
        return SpareCategory::create($data);
    }

    public function update(SpareCategory $category, array $data)
    {
        $category->update($data);
        return $category;
    }

    public function delete(SpareCategory $category)
    {
        return $category->delete();
    }
}
