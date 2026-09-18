<?php

use App\Http\Middleware\LoadUserRbac;
use App\Http\Middleware\RequirePermission;
use Illuminate\Auth\Access\AuthorizationException;
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

        $middleware->web(append: [
            LoadUserRbac::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/v1/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

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

            return redirect()->route('login');
        });
    })->create();
