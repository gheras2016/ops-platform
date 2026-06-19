<?php

namespace App\Services;

use App\Models\Department;

class DepartmentService
{
    public function list()
    {
        return Department::latest()->paginate(20);
    }

    public function create(array $data)
    {
        return Department::create($data);
    }

    public function update(Department $department, array $data)
    {
        $department->update($data);
        return $department;
    }

    public function delete(Department $department)
    {
        return $department->delete();
    }
}
