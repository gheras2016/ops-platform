<?php

namespace App\Providers;

use App\Models\PartRequest;
use App\Models\PurchaseRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\PartRequestPolicy;
use App\Policies\PurchaseRequestPolicy;
use App\Policies\TicketPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Ticket::class => TicketPolicy::class,
        PartRequest::class => PartRequestPolicy::class,
        PurchaseRequest::class => PurchaseRequestPolicy::class,
    ];

    public function boot(): void
    {
        // Super admin bypasses every gate check.
        Gate::before(function (User $user, string $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        // Coarse-grained gates used by management screens.
        Gate::define('admin-access', fn (User $user) => $user->isAdmin());
        Gate::define('platform-access', fn (User $user) => $user->isSuperAdmin());
        Gate::define('view-reports', fn (User $user) => $user->isAdmin() || $user->isDepartmentHead());
        // Inventory / spare-parts catalogue + procurement (admins or the warehouse manager).
        Gate::define('inventory-access', fn (User $user) => $user->canManageInventory());
        // Read-only stock visibility for field staff (techs, heads) + warehouse/finance/admin.
        // Anyone working on tickets may check availability; plain requesters may not.
        Gate::define('view-inventory', fn (User $user) => $user->isAdmin()
            || $user->isWarehouseManager()
            || $user->isDepartmentHead()
            || $user->isTechnician()
            || $user->isFinanceManager());
        // Finance approval of purchases (admins or the finance manager).
        Gate::define('finance-access', fn (User $user) => $user->canApprovePurchasing());
    }
}
