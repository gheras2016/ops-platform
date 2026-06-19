<?php

namespace App\Services;

use Spatie\Permission\Models\Role;

class RoleService
{
    public function list()
    {
        return Role::latest()->paginate(20);
    }

    public function create(array $data)
    {
        return Role::create($data);
    }

    public function update(Role $role, array $data)
    {
        $role->update($data);
        return $role;
    }

    public function delete(Role $role)
    {
        return $role->delete();
    }
}
