<?php

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\User;

class PurchaseRequestPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function view(User $user, PurchaseRequest $pr): bool
    {
        return $user->isCompanyAdmin()
            || $user->canManageInventory()
            || $user->canApprovePurchasing()
            || $pr->requested_by === $user->id
            || $user->managesDepartment($pr->department_id)
            || $user->managesDepartment($pr->current_dept_id);
    }

    /** Department heads (and admins) raise purchase requests. */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isDepartmentHead() || $user->isWarehouseManager();
    }

    public function update(User $user, PurchaseRequest $pr): bool
    {
        return $pr->status === PurchaseRequest::STATUS_DRAFT
            && ($user->isCompanyAdmin() || $pr->requested_by === $user->id);
    }

    /** Whoever owns the current approval step. */
    public function decide(User $user, PurchaseRequest $pr): bool
    {
        if ($pr->canDeptDecide()) {
            return $user->isCompanyAdmin() || $user->managesDepartment($pr->current_dept_id);
        }
        if ($pr->canFinanceDecide()) {
            return $user->canApprovePurchasing();
        }

        return false;
    }

    public function receive(User $user, PurchaseRequest $pr): bool
    {
        return $user->canManageInventory();
    }
}
