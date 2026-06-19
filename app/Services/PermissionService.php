<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function list()
    {
        return Permission::latest()->paginate(20);
    }

    public function create(array $data)
    {
        return Permission::create($data);
    }

    public function update(Permission $permission, array $data)
    {
        $permission->update($data);
        return $permission;
    }

    public function delete(Permission $permission)
    {
        return $permission->delete();
    }
}
