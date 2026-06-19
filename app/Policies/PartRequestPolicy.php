<?php

namespace App\Policies;

use App\Models\PartRequest;
use App\Models\User;

class PartRequestPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function view(User $user, PartRequest $request): bool
    {
        return $user->isCompanyAdmin()
            || $user->canManageInventory()
            || $request->requested_by === $user->id
            || $user->managesDepartment($request->department_id);
    }

    /** Department head of the request's department (or admin). */
    public function approve(User $user, PartRequest $request): bool
    {
        return $user->isCompanyAdmin() || $user->managesDepartment($request->department_id);
    }

    /** Warehouse manager / admin issues stock. */
    public function issue(User $user, PartRequest $request): bool
    {
        return $user->canManageInventory();
    }

    public function cancel(User $user, PartRequest $request): bool
    {
        return $user->isCompanyAdmin()
            || $request->requested_by === $user->id
            || $user->managesDepartment($request->department_id);
    }
}
