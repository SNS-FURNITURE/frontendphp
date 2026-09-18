<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\FundingRequestController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SalesOrderController;
use App\Http\Controllers\Api\StockLevelController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\AuthenticateJwt;
use App\Http\Middleware\EnsureInvoiceLaunchRole;
use App\Http\Middleware\RequireAdminRole;
use App\Http\Middleware\VerifyApiCsrf;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::get('/api/v1/health', HealthController::class);

Route::prefix('api/v1')->middleware([VerifyApiCsrf::class])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('csrf-token', [AuthController::class, 'csrfToken']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me'])->middleware(AuthenticateJwt::class);
    });

    $authLaunch = [AuthenticateJwt::class, EnsureInvoiceLaunchRole::class];
    $view = [...$authLaunch, 'permission:finance,view'];
    $create = [...$authLaunch, 'permission:finance,create'];
    $edit = [...$authLaunch, 'permission:finance,edit'];

    Route::get('profile', [ProfileController::class, 'show'])->middleware($authLaunch);
    Route::patch('profile', [ProfileController::class, 'update'])->middleware($authLaunch);
    Route::patch('profile/password', [ProfileController::class, 'updatePassword'])->middleware($authLaunch);

    Route::get('users', [UserController::class, 'index'])->middleware($authLaunch);
    Route::post('users', [UserController::class, 'store'])->middleware([...$authLaunch, RequireAdminRole::class]);
    Route::get('audit-log', [AuditLogController::class, 'index'])->middleware($authLaunch);

    Route::get('invoices/next-number', [InvoiceController::class, 'nextNumber'])->middleware($create);
    Route::get('invoices', [InvoiceController::class, 'index'])->middleware($view);
    Route::post('invoices', [InvoiceController::class, 'store'])->middleware($create);
    Route::get('invoices/{id}', [InvoiceController::class, 'show'])->middleware($view)->whereNumber('id');
    Route::patch('invoices/{id}', [InvoiceController::class, 'update'])->middleware($edit)->whereNumber('id');
    Route::patch('invoices/{id}/status', [InvoiceController::class, 'updateStatus'])->middleware($edit)->whereNumber('id');
    Route::get('invoices/{id}/document', [DocumentController::class, 'invoiceDocument'])->middleware($view)->whereNumber('id');

    Route::get('payments', [PaymentController::class, 'index'])->middleware($view);
    Route::post('payments', [PaymentController::class, 'store'])->middleware($create);

    Route::post('documents/render', [DocumentController::class, 'render'])->middleware($view);
    Route::post('documents/compose', [DocumentController::class, 'compose'])->middleware($create);

    // Parties + sales (Express: auth only; party approve is role-gated in controller)
    Route::get('parties', [PartyController::class, 'index'])->middleware($authLaunch);
    Route::post('parties', [PartyController::class, 'store'])->middleware($authLaunch);
    Route::patch('parties/{id}/approve', [PartyController::class, 'approve'])->middleware($authLaunch)->whereNumber('id');

    $salesRoutes = function () {
        Route::get('quota', [SalesOrderController::class, 'quota']);
        Route::get('/', [SalesOrderController::class, 'index']);
        Route::post('/', [SalesOrderController::class, 'store']);
        Route::get('{id}', [SalesOrderController::class, 'show'])->whereNumber('id');
        Route::patch('{id}/status', [SalesOrderController::class, 'updateStatus'])->whereNumber('id');
        Route::post('{id}/lines', [SalesOrderController::class, 'addLine'])->whereNumber('id');
    };

    Route::prefix('sales-orders')->middleware($authLaunch)->group($salesRoutes);
    Route::prefix('sales')->middleware($authLaunch)->group($salesRoutes);

    // Funding (Express: auth+launch only; mounted at /funding and /funding-requests)
    $fundingRoutes = function () {
        Route::get('/', [FundingRequestController::class, 'index']);
        Route::post('/', [FundingRequestController::class, 'store']);
        Route::patch('{id}', [FundingRequestController::class, 'update'])->whereNumber('id');
    };
    Route::prefix('funding')->middleware($authLaunch)->group($fundingRoutes);
    Route::prefix('funding-requests')->middleware($authLaunch)->group($fundingRoutes);

    // Inventory (Express: auth+launch only)
    Route::get('items', [ItemController::class, 'index'])->middleware($authLaunch);
    Route::post('items', [ItemController::class, 'store'])->middleware($authLaunch);
    Route::get('items/low-stock', [ItemController::class, 'lowStock'])->middleware($authLaunch);
    Route::get('items/{id}/stock', [ItemController::class, 'stock'])->middleware($authLaunch)->whereNumber('id');
    Route::get('stock-levels', [StockLevelController::class, 'index'])->middleware($authLaunch);
    Route::get('stock-movements', [StockMovementController::class, 'index'])->middleware($authLaunch);
    Route::post('stock-movements', [StockMovementController::class, 'store'])->middleware($authLaunch);
});
