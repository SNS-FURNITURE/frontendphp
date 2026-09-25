<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $module, string $action): Response
    {
        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        if (! $user) {
            return ApiResponse::error('Authentication required', 'UNAUTHORIZED', 401);
        }

        if (! $user->hasPermission($module, $action)) {
            return ApiResponse::error('Insufficient permissions for this action', 'FORBIDDEN', 403);
        }

        return $next($request);
    }
}
