<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\User;
use App\Policies\InvoicePolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::policy(Invoice::class, InvoicePolicy::class);

        Gate::define('finance-view', fn (User $user) => $user->hasInvoiceLaunchRole() && $user->hasPermission('finance', 'view'));
        Gate::define('finance-create', fn (User $user) => ! $user->isAdmin() && $user->hasInvoiceLaunchRole() && $user->hasPermission('finance', 'create'));
        Gate::define('finance-edit', fn (User $user) => ! $user->isAdmin() && $user->hasInvoiceLaunchRole() && $user->hasPermission('finance', 'edit'));
    }
}
