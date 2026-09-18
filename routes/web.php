<?php

use App\Http\Controllers\Web\InvoiceWebController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\PaymentWebController;
use App\Http\Controllers\Web\ProfileWebController;
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
});
