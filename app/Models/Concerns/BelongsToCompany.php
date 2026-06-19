<?php

namespace App\Models\Concerns;

use App\Models\Scopes\CompanyScope;
use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Multi-tenant helper. Any model using this trait is automatically:
 *  - filtered to the current user's company on every query (CompanyScope)
 *  - stamped with that company_id on create.
 *
 * super_admin users bypass the scope (company_id === null) and see all tenants.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (empty($model->company_id) && ($companyId = static::currentCompanyId())) {
                $model->company_id = $companyId;
            }
        });
    }

    public static function currentCompanyId(): ?int
    {
        $user = auth()->user();

        return $user?->company_id;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
