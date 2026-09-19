<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        if (! $user || ! $user->isAdmin()) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error('Admin role required', 'FORBIDDEN', 403);
            }

            abort(403, 'Admin role required');
        }

        return $next($request);
    }
}
