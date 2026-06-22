<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketActionController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect()->route('login'));

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'read'])->name('notifications.read');
    Route::post('notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('notifications.read-all');

    /*
    |--------------------------------------------------------------------------
    | Tickets — core workflow
    |--------------------------------------------------------------------------
    */
    Route::controller(TicketActionController::class)
        ->prefix('tickets/{ticket}')
        ->name('tickets.')
        ->group(function () {
            Route::post('assign', 'assign')->name('assign');
            Route::post('accept', 'accept')->name('accept');
            Route::post('start', 'start')->name('start');
            Route::post('pause', 'pause')->name('pause');
            Route::post('resume', 'resume')->name('resume');
            Route::post('progress', 'progress')->name('progress');
            Route::post('resolve', 'resolve')->name('resolve');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
            Route::post('cancel', 'cancel')->name('cancel');
            Route::post('comment', 'comment')->name('comment');
            Route::post('attachments', 'attach')->name('attach');
            Route::delete('attachments/{attachment}', 'detach')->name('detach');
        });

    // Spare-parts requests tied to a ticket
    Route::post('tickets/{ticket}/part-requests', [\App\Http\Controllers\PartRequestController::class, 'store'])->name('tickets.part-requests.store');
    Route::get('part-requests', [\App\Http\Controllers\PartRequestController::class, 'index'])->name('part-requests.index');
    Route::controller(\App\Http\Controllers\PartRequestController::class)
        ->prefix('part-requests/{partRequest}')->name('part-requests.')
        ->group(function () {
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
            Route::post('issue', 'issue')->name('issue');
            Route::post('cancel', 'cancel')->name('cancel');
            Route::post('convert', 'convert')->name('convert');
        });

    // Purchase requests — approval chain (dept tree → finance) + execution (stock/direct)
    Route::get('purchase-requests', [\App\Http\Controllers\PurchaseRequestController::class, 'index'])->name('purchase-requests.index');
    Route::get('purchase-requests/create', [\App\Http\Controllers\PurchaseRequestController::class, 'create'])->name('purchase-requests.create');
    Route::post('purchase-requests', [\App\Http\Controllers\PurchaseRequestController::class, 'store'])->name('purchase-requests.store');
    Route::controller(\App\Http\Controllers\PurchaseRequestController::class)
        ->prefix('purchase-requests/{purchaseRequest}')->name('purchase-requests.')
        ->group(function () {
            Route::get('/', 'show')->name('show');
            Route::get('print', 'print')->name('print');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
            Route::post('receive', 'receive')->name('receive');
        });

    Route::get('tickets/board', [TicketController::class, 'board'])->name('tickets.board');
    Route::get('tickets/{ticket}/report', [TicketController::class, 'report'])->name('tickets.report');
    Route::resource('tickets', TicketController::class);

    /*
    |--------------------------------------------------------------------------
    | Reports & analytics
    |--------------------------------------------------------------------------
    */
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export/{format}', [ReportController::class, 'export'])
        ->whereIn('format', ['csv', 'xlsx', 'pdf'])->name('reports.export');

    /*
    |--------------------------------------------------------------------------
    | Administration
    |--------------------------------------------------------------------------
    */
    // Visual identity (theme) settings
    Route::get('settings/theme', [\App\Http\Controllers\SettingsController::class, 'theme'])->name('settings.theme');
    Route::post('settings/theme', [\App\Http\Controllers\SettingsController::class, 'updateTheme'])->name('settings.theme.update');
    Route::post('settings/theme/reset', [\App\Http\Controllers\SettingsController::class, 'resetTheme'])->name('settings.theme.reset');

    // Company self-service subscription (company admin)
    Route::get('subscription', [\App\Http\Controllers\CompanySubscriptionController::class, 'show'])->name('company.subscription');
    Route::post('subscription/checkout', [\App\Http\Controllers\CompanySubscriptionController::class, 'checkout'])->name('company.subscription.checkout');
    Route::get('subscription/callback/{payment}', [\App\Http\Controllers\CompanySubscriptionController::class, 'callback'])->name('company.subscription.callback');

    // Platform subscription management (super-admin)
    Route::get('subscriptions', [\App\Http\Controllers\SubscriptionAdminController::class, 'index'])->name('subscriptions.index');
    Route::post('subscriptions/{company}/activate', [\App\Http\Controllers\SubscriptionAdminController::class, 'activate'])->name('subscriptions.activate');
    Route::post('subscriptions/{company}/extend', [\App\Http\Controllers\SubscriptionAdminController::class, 'extendTrial'])->name('subscriptions.extend');
    Route::post('subscriptions/{company}/suspend', [\App\Http\Controllers\SubscriptionAdminController::class, 'suspend'])->name('subscriptions.suspend');

    Route::post('companies/{company}/toggle', [CompanyController::class, 'toggle'])->name('companies.toggle');
    Route::get('companies/{company}/export', [CompanyController::class, 'export'])->name('companies.export');
    Route::resource('companies', CompanyController::class)->except('show');
    Route::resource('departments', DepartmentController::class);
    Route::post('locations/quick', [\App\Http\Controllers\LocationController::class, 'quickStore'])->name('locations.quick');
    Route::resource('locations', \App\Http\Controllers\LocationController::class);
    Route::resource('users', UserController::class);

    /*
    |--------------------------------------------------------------------------
    | Secondary modules (inventory / spare parts / purchasing)
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:inventory-access')->group(function () {
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('items/template', [\App\Http\Controllers\ItemController::class, 'template'])->name('items.template');
            Route::post('items/import', [\App\Http\Controllers\ItemController::class, 'import'])->name('items.import');
            Route::resource('items', \App\Http\Controllers\ItemController::class);
            Route::resource('categories', \App\Http\Controllers\CategoryController::class);
        });

        Route::resource('spare-categories', \App\Http\Controllers\SpareCategoryController::class)->except('show');
        Route::get('spare-parts/template', [\App\Http\Controllers\SparePartController::class, 'template'])->name('spare-parts.template');
        Route::post('spare-parts/import', [\App\Http\Controllers\SparePartController::class, 'import'])->name('spare-parts.import');
        Route::resource('spare-parts', \App\Http\Controllers\SparePartController::class);
        Route::resource('stock-transactions', \App\Http\Controllers\StockTransactionController::class);
    });
});
