<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts queries to the authenticated user's company.
 *
 * Bypassed when:
 *  - there is no authenticated user (console, seeders, jobs), or
 *  - the user has no company_id (super_admin / platform owner).
 */
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if (! $user || empty($user->company_id)) {
            return;
        }

        $builder->where($model->getTable() . '.company_id', $user->company_id);
    }
}
