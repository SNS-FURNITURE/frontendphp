<?php

use App\Http\Controllers\Web\AdminAuditWebController;
use App\Http\Controllers\Web\AdminUserWebController;
use App\Http\Controllers\Web\AllocationWebController;
use App\Http\Controllers\Web\BomWebController;
use App\Http\Controllers\Web\CustomerWebController;
use App\Http\Controllers\Web\DeliveryWebController;
use App\Http\Controllers\Web\FundingWebController;
use App\Http\Controllers\Web\InventoryItemWebController;
use App\Http\Controllers\Web\InventoryMovementWebController;
use App\Http\Controllers\Web\InventoryStockWebController;
use App\Http\Controllers\Web\InvoiceWebController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\MaterialRequestWebController;
use App\Http\Controllers\Web\OutboundWebController;
use App\Http\Controllers\Web\PaymentWebController;
use App\Http\Controllers\Web\ProductionOrderWebController;
use App\Http\Controllers\Web\ProfileWebController;
use App\Http\Controllers\Web\SalesOrderWebController;
use App\Http\Controllers\Web\SalesQuotaWebController;
use App\Http\Middleware\EnsureWebInvoiceAccess;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('invoices.index')
        : redirect()->route('login');
});

Route::get('/workspace', fn () => redirect()->route('invoices.index'))
    ->middleware('auth')
    ->name('workspace');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/logout/idle', [LoginController::class, 'idleLogout'])
    ->middleware('auth')
    ->name('logout.idle');

Route::middleware(['auth', EnsureWebInvoiceAccess::class])->group(function () {
    Route::get('/profile', [ProfileWebController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileWebController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileWebController::class, 'updatePassword'])->name('profile.password');

    Route::get('/finance/invoices', [InvoiceWebController::class, 'index'])->name('invoices.index');
    Route::get('/finance/invoices/create', [InvoiceWebController::class, 'create'])->name('invoices.create');
    Route::get('/finance/invoices/new', [InvoiceWebController::class, 'create'])->name('invoices.new');
    Route::post('/finance/invoices', [InvoiceWebController::class, 'store'])->name('invoices.store');
    Route::post('/finance/invoices/compose-pdf', [InvoiceWebController::class, 'composePdf'])->name('invoices.compose-pdf');
    Route::get('/finance/invoices/{invoice}', [InvoiceWebController::class, 'show'])->name('invoices.show');
    Route::get('/finance/invoices/{invoice}/edit', [InvoiceWebController::class, 'edit'])->name('invoices.edit');
    Route::put('/finance/invoices/{invoice}', [InvoiceWebController::class, 'update'])->name('invoices.update');
    Route::patch('/finance/invoices/{invoice}/status', [InvoiceWebController::class, 'updateStatus'])->name('invoices.status');
    Route::get('/finance/invoices/{invoice}/document', [InvoiceWebController::class, 'document'])->name('invoices.document');

    Route::get('/finance/payments', [PaymentWebController::class, 'index'])->name('payments.index');
    Route::post('/finance/payments', [PaymentWebController::class, 'store'])->name('payments.store');

    Route::get('/finance/allocations', [AllocationWebController::class, 'index'])->name('finance.allocations');

    Route::get('/funding', [FundingWebController::class, 'index'])->name('funding.index');
    Route::post('/funding', [FundingWebController::class, 'store'])->name('funding.store');
    Route::patch('/funding/{funding}/status', [FundingWebController::class, 'updateStatus'])->name('funding.status');

    Route::get('/inventory/items', [InventoryItemWebController::class, 'index'])->name('inventory.items');
    Route::post('/inventory/items', [InventoryItemWebController::class, 'store'])->name('inventory.items.store');
    Route::get('/inventory/stock', [InventoryStockWebController::class, 'index'])->name('inventory.stock');
    Route::get('/inventory/low-stock', [InventoryStockWebController::class, 'lowStock'])->name('inventory.low-stock');
    Route::get('/inventory/movements', [InventoryMovementWebController::class, 'index'])->name('inventory.movements');
    Route::post('/inventory/movements', [InventoryMovementWebController::class, 'store'])->name('inventory.movements.store');
    Route::get('/inventory/outbound', [OutboundWebController::class, 'index'])->name('inventory.outbound');
    Route::get('/inventory/material-requests', fn () => redirect()->route('material-requests.index'))->name('inventory.material-requests');

    Route::get('/material-requests', [MaterialRequestWebController::class, 'index'])->name('material-requests.index');
    Route::post('/material-requests', [MaterialRequestWebController::class, 'store'])->name('material-requests.store');
    Route::patch('/material-requests/{materialRequest}', [MaterialRequestWebController::class, 'update'])->name('material-requests.update');

    Route::get('/production', [ProductionOrderWebController::class, 'index'])->name('production.index');
    Route::post('/production', [ProductionOrderWebController::class, 'store'])->name('production.store');
    Route::patch('/production/{order}/status', [ProductionOrderWebController::class, 'updateStatus'])->name('production.status');
    Route::get('/manufacturing/production-orders', fn () => redirect()->route('production.index'))->name('manufacturing.production-orders');

    Route::get('/manufacturing/boms', [BomWebController::class, 'index'])->name('manufacturing.boms');
    Route::post('/manufacturing/boms', [BomWebController::class, 'store'])->name('manufacturing.boms.store');
    Route::patch('/manufacturing/boms/{bom}', [BomWebController::class, 'update'])->name('manufacturing.boms.update');
    Route::delete('/manufacturing/boms/{bom}', [BomWebController::class, 'destroy'])->name('manufacturing.boms.destroy');
    Route::post('/manufacturing/boms/{bom}/lines', [BomWebController::class, 'storeLine'])->name('manufacturing.boms.lines.store');
    Route::patch('/manufacturing/boms/{bom}/lines/{line}', [BomWebController::class, 'updateLine'])->name('manufacturing.boms.lines.update');
    Route::delete('/manufacturing/boms/{bom}/lines/{line}', [BomWebController::class, 'destroyLine'])->name('manufacturing.boms.lines.destroy');

    Route::get('/production/deliveries', [DeliveryWebController::class, 'index'])->name('production.deliveries');
    Route::post('/production/deliveries', [DeliveryWebController::class, 'store'])->name('production.deliveries.store');
    Route::patch('/production/deliveries/{delivery}/status', [DeliveryWebController::class, 'updateStatus'])->name('production.deliveries.status');

    Route::get('/sales/customers', [CustomerWebController::class, 'index'])->name('sales.customers');
    Route::post('/sales/customers', [CustomerWebController::class, 'store'])->name('sales.customers.store');
    Route::patch('/sales/customers/{party}/approve', [CustomerWebController::class, 'approve'])->name('sales.customers.approve');

    Route::get('/orders/requests', [SalesOrderWebController::class, 'index'])->name('orders.requests');
    Route::post('/orders/requests', [SalesOrderWebController::class, 'store'])->name('orders.requests.store');
    Route::get('/sales/orders', fn () => redirect()->route('orders.requests'))->name('sales.orders');
    Route::get('/sales/orders/{order}', [SalesOrderWebController::class, 'show'])->name('sales.orders.show');
    Route::patch('/sales/orders/{order}/status', [SalesOrderWebController::class, 'updateStatus'])->name('sales.orders.status');
    Route::post('/sales/orders/{order}/lines', [SalesOrderWebController::class, 'addLine'])->name('sales.orders.lines');

    Route::get('/sales/quota', [SalesQuotaWebController::class, 'show'])->name('sales.quota');

    Route::get('/admin/users', [AdminUserWebController::class, 'index'])->name('admin.users');
    Route::post('/admin/users', [AdminUserWebController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/audit-log', [AdminAuditWebController::class, 'index'])->name('admin.audit');
});
