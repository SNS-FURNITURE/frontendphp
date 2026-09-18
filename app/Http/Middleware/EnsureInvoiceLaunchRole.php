<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInvoiceLaunchRole
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        if (! $user) {
            return ApiResponse::error('Authentication required', 'UNAUTHORIZED', 401);
        }

        if (! $user->hasInvoiceLaunchRole()) {
            return ApiResponse::error(
                'Invoice system only — your role is not enabled for this launch',
                'LAUNCH_ROLE_FORBIDDEN',
                403,
            );
        }

        return $next($request);
    }
}
