<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'manage companies', 'manage users', 'manage departments',
            'manage tickets', 'assign tickets', 'approve tickets', 'work tickets',
            'create tickets', 'view reports', 'manage inventory', 'manage purchasing',
            // Spare-parts workflow (additive)
            'issue parts', 'approve parts request', 'request parts',
            // Procurement / finance (additive)
            'convert to purchase', 'approve purchasing', 'receive stock',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            User::ROLE_SUPER_ADMIN => $permissions,
            User::ROLE_COMPANY_ADMIN => [
                'manage users', 'manage departments', 'manage tickets', 'assign tickets',
                'approve tickets', 'create tickets', 'view reports', 'manage inventory', 'manage purchasing',
                'issue parts', 'approve parts request',
                'convert to purchase', 'approve purchasing', 'receive stock',
            ],
            User::ROLE_WAREHOUSE_MANAGER => [
                'manage inventory', 'manage purchasing', 'issue parts', 'view reports', 'create tickets',
                'convert to purchase', 'receive stock',
            ],
            User::ROLE_FINANCE_MANAGER => [
                'approve purchasing', 'view reports', 'create tickets',
            ],
            User::ROLE_DEPARTMENT_HEAD => [
                'manage tickets', 'assign tickets', 'approve tickets', 'create tickets', 'view reports',
                'approve parts request',
            ],
            User::ROLE_TECHNICIAN => ['work tickets', 'create tickets', 'request parts'],
            User::ROLE_REQUESTER => ['create tickets'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions);
        }
    }
}
