<?php

namespace App\Observers;

use App\Models\Company;

class CompanyObserver
{
    /** Every new tenant starts on a free trial unless explicitly set otherwise. */
    public function creating(Company $company): void
    {
        if (is_null($company->is_active)) {
            $company->is_active = true;
        }

        if (empty($company->subscription_status)) {
            $company->subscription_status = Company::SUB_TRIAL;
        }

        if ($company->subscription_status === Company::SUB_TRIAL && is_null($company->trial_ends_at)) {
            $company->trial_ends_at = now()->addDays(7);
        }
    }
}
