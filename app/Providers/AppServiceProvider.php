<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\User;
use App\Policies\InvoicePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Builder::macro('latestFirst', function (?string $column = null) {
            /** @var Builder $this */
            $model = $this->getModel();
            $table = $model->getTable();
            $key = $model->getKeyName();

            if ($column === null) {
                $updatedAt = $model->getUpdatedAtColumn();
                if ($updatedAt !== null && Schema::hasColumn($table, $updatedAt)) {
                    $column = $updatedAt;
                } else {
                    $createdAt = $model->getCreatedAtColumn();
                    if ($createdAt !== null && Schema::hasColumn($table, $createdAt)) {
                        $column = $createdAt;
                    } else {
                        $column = $key;
                    }
                }
            } elseif (! Schema::hasColumn($table, $column)) {
                $createdAt = $model->getCreatedAtColumn();
                $column = ($createdAt !== null && Schema::hasColumn($table, $createdAt))
                    ? $createdAt
                    : $key;
            }

            return $this
                ->orderByDesc("{$table}.{$column}")
                ->orderByDesc("{$table}.{$key}");
        });

        Gate::policy(Invoice::class, InvoicePolicy::class);

        Gate::define('finance-view', fn (User $user) => $user->hasInvoiceLaunchRole() && (
            $user->hasPermission('finance', 'view') || $user->hasRole('marketing_manager')
        ));
        Gate::define('finance-create', fn (User $user) => ! $user->isAdmin()
            && ! $user->hasRole('marketing_manager')
            && $user->hasInvoiceLaunchRole()
            && $user->hasPermission('finance', 'create'));
        Gate::define('finance-edit', fn (User $user) => ! $user->isAdmin()
            && ! $user->hasRole('marketing_manager')
            && $user->hasInvoiceLaunchRole()
            && $user->hasPermission('finance', 'edit'));
        Gate::define('finance-approve', fn (User $user) => $user->isAdmin()
            || $user->hasRole('marketing_manager')
            || ($user->hasInvoiceLaunchRole() && $user->hasPermission('finance', 'approve')));

        $this->configureUrlScheme();
        $this->configureRateLimiting();
    }

    private function configureUrlScheme(): void
    {
        $forceHttps = filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN)
            || app()->environment('production');

        if ($forceHttps) {
            URL::forceScheme('https');
        }
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $id = Str::lower((string) (
                $request->input('email')
                ?? $request->input('login')
                ?? $request->input('username')
                ?? ''
            ));

            return [
                Limit::perMinute(5)->by(Str::transliterate($id.'|'.$request->ip())),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        RateLimiter::for('api', function (Request $request) {
            $key = optional($request->user())->id ?: $request->ip();

            return Limit::perMinute((int) env('API_RATE_LIMIT', 120))->by((string) $key);
        });

        RateLimiter::for('public-intake', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
