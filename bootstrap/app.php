<?php

use App\Http\Middleware\LoadUserRbac;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => RequirePermission::class,
            'admin' => \App\Http\Middleware\RequireAdminRole::class,
        ]);

        // Behind Railway / Render / Cloudflare / load balancers.
        $trusted = env('TRUSTED_PROXIES', '*');
        $middleware->trustProxies(
            at: $trusted === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', (string) $trusted)))),
        );

        if ((string) env('APP_ENV', 'production') === 'production' && filled(env('APP_URL'))) {
            $host = parse_url((string) env('APP_URL'), PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                $middleware->trustHosts(
                    at: fn () => [$host, '^(.+\.)?'.preg_quote($host, '/').'$'],
                    subdomains: true,
                );
            }
        }

        $middleware->web(append: [
            LoadUserRbac::class,
            SecurityHeaders::class,
        ]);

        $middleware->api(append: [
            SecurityHeaders::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/v1/*',
        ]);

        // Do NOT redirect guests to login — unauthenticated requests get 404
        // so the ERP is completely invisible to anyone who doesn't know /login.
        $middleware->redirectGuestsTo(fn () => abort(404));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Unauthenticated requests return 404 — the ERP doesn't reveal itself.
        // Only /login is a known public endpoint.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null; // Let the JSON handler deal with it.
            }

            abort(404);
        });

        // Authorisation failures: logged-in users who lack permission
        // are sent back to their home page (not exposed externally).
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null;
            }

            $user = $request->user();
            if ($user) {
                return redirect()
                    ->route($user->preferredHomeRouteName())
                    ->with('status', 'You do not have access to that page.');
            }

            abort(404);
        });
    })->create();
