<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PartRequestActionController;
use App\Http\Controllers\Api\PartRequestController;
use App\Http\Controllers\Api\PurchaseRequestActionController;
use App\Http\Controllers\Api\PurchaseRequestController;
use App\Http\Controllers\Api\SparePartController;
use App\Http\Controllers\Api\TicketActionController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketSparePartController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1 (official mobile client)
|--------------------------------------------------------------------------
|
| Token-authenticated (Sanctum) JSON API. Business logic is shared with the
| web app via the same Services + Policies; only the transport differs.
| Tenant isolation + active-account gating apply exactly as on the web.
|
*/

Route::prefix('v1')->group(function () {

    // Public: obtain a token.
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    // Authenticated: token + active account required.
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // ---- Tickets module ----
        Route::get('dashboard/stats', [TicketController::class, 'stats']);
        Route::get('tickets/meta', [TicketController::class, 'meta']);
        Route::get('tickets', [TicketController::class, 'index']);
        Route::post('tickets', [TicketController::class, 'store']);
        Route::get('tickets/{ticket}', [TicketController::class, 'show']);

        // Spare-parts catalogue search (in-ticket picker)
        Route::get('spare-parts', [SparePartController::class, 'index']);

        // Used spare parts on a ticket (record while working; deducted at close)
        Route::get('tickets/{ticket}/spare-parts', [TicketSparePartController::class, 'index']);
        Route::post('tickets/{ticket}/spare-parts', [TicketSparePartController::class, 'store']);
        Route::delete('tickets/{ticket}/spare-parts/{sparePart}', [TicketSparePartController::class, 'destroy']);

        // Part requests tied to a ticket (catalogue or non-catalogue)
        Route::get('tickets/{ticket}/part-requests', [PartRequestController::class, 'index']);
        Route::post('tickets/{ticket}/part-requests', [PartRequestController::class, 'store']);

        // Spare-part request approvals (head approves/rejects, warehouse issues)
        Route::get('part-requests', [PartRequestActionController::class, 'inbox']);
        Route::controller(PartRequestActionController::class)
            ->prefix('part-requests/{partRequest}')->group(function () {
                Route::post('approve', 'approve');
                Route::post('reject', 'reject');
                Route::post('issue', 'issue');
                Route::post('cancel', 'cancel');
            });

        // Lifecycle transitions
        Route::controller(TicketActionController::class)
            ->prefix('tickets/{ticket}')->group(function () {
                Route::post('assign', 'assign');
                Route::post('accept', 'accept');
                Route::post('start', 'start');
                Route::post('pause', 'pause');
                Route::post('resume', 'resume');
                Route::post('progress', 'progress');
                Route::post('resolve', 'resolve');
                Route::post('approve', 'approve');
                Route::post('reject', 'reject');
                Route::post('cancel', 'cancel');
                Route::post('comment', 'comment');
            });

        // ---- Notifications ----
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
        Route::post('notifications/{id}/read', [NotificationController::class, 'read']);

        // ---- Inventory (read-only field visibility) ----
        Route::middleware('can:view-inventory')->group(function () {
            Route::get('inventory', [InventoryController::class, 'index']);
            Route::get('inventory/summary', [InventoryController::class, 'summary']);
            Route::get('inventory/low-stock', [InventoryController::class, 'lowStock']);
            Route::get('inventory/categories', [InventoryController::class, 'categories']);
            Route::get('inventory/{sparePart}', [InventoryController::class, 'show']);
            Route::get('inventory/{sparePart}/movements', [InventoryController::class, 'movements']);
        });

        // ---- Purchase requests (procurement loop) ----
        Route::get('purchase-requests', [PurchaseRequestController::class, 'index']);
        Route::get('purchase-requests/meta', [PurchaseRequestController::class, 'meta']);
        Route::post('purchase-requests', [PurchaseRequestController::class, 'store']);
        Route::get('purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'show']);
        Route::controller(PurchaseRequestActionController::class)
            ->prefix('purchase-requests/{purchaseRequest}')->group(function () {
                Route::post('approve', 'approve');
                Route::post('reject', 'reject');
                Route::post('receive', 'receive');
            });
    });
});

// Legacy default endpoint (kept for compatibility).
Route::middleware('auth:sanctum')->get('/user', fn (Request $request) => $request->user());
