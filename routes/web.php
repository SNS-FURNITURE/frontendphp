<?php

use App\Http\Controllers\Web\AdminAuditWebController;
use App\Http\Controllers\Web\AdminUserWebController;
use App\Http\Controllers\Web\CustomerWebController;
use App\Http\Controllers\Web\InvoiceWebController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\PaymentWebController;
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
