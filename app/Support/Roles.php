<?php

namespace App\Support;

use App\Models\User;

/**
 * Display helpers for roles (Arabic labels / colors).
 */
class Roles
{
    public const LABELS = [
        User::ROLE_SUPER_ADMIN => 'مدير المنصة',
        User::ROLE_COMPANY_ADMIN => 'مدير النظام',
        User::ROLE_DEPARTMENT_HEAD => 'رئيس قسم',
        User::ROLE_WAREHOUSE_MANAGER => 'مدير المخزون',
        User::ROLE_FINANCE_MANAGER => 'مدير المالية',
        User::ROLE_TECHNICIAN => 'فني',
        User::ROLE_REQUESTER => 'مستخدم',
    ];

    public const COLORS = [
        User::ROLE_SUPER_ADMIN => 'red',
        User::ROLE_COMPANY_ADMIN => 'indigo',
        User::ROLE_DEPARTMENT_HEAD => 'blue',
        User::ROLE_WAREHOUSE_MANAGER => 'amber',
        User::ROLE_FINANCE_MANAGER => 'green',
        User::ROLE_TECHNICIAN => 'teal',
        User::ROLE_REQUESTER => 'gray',
    ];

    public static function label(?string $role): string
    {
        return self::LABELS[$role] ?? ($role ?? '—');
    }

    public static function color(?string $role): string
    {
        return self::COLORS[$role] ?? 'gray';
    }
}
