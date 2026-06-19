<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * The API shape of an authenticated user — consumed by the mobile client for
 * multi-tenant context (company) and role/ability-based UI (RBAC).
 */
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'job_title' => $this->job_title,
            'is_active' => (bool) $this->is_active,

            // Tenant context — the app derives the company from the logged-in user.
            'company' => $this->company ? [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'logo_url' => $this->company->logoUrl(),
                'primary_color' => $this->company->primary_color,
                'sidebar_color' => $this->company->sidebar_color,
                'bg_color' => $this->company->bg_color,
            ] : null,

            // RBAC — role names + the coarse gates this user passes, for conditional UI.
            'role' => $this->roles->pluck('name')->first(), // kept for backward compatibility
            'roles' => $this->roles->pluck('name')->values(),
            'abilities' => $this->abilities(),
        ];
    }

    /** Coarse capability flags mirroring the web app's Gates. */
    protected function abilities(): array
    {
        $user = $this->resource;

        return collect(['platform-access', 'admin-access', 'view-reports', 'inventory-access', 'view-inventory', 'finance-access'])
            ->filter(fn ($gate) => Gate::forUser($user)->allows($gate))
            ->values()
            ->all();
    }
}
