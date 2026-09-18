<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function __construct(private JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('sns_token');
        if (! $token) {
            $auth = $request->header('Authorization');
            if (is_string($auth) && str_starts_with($auth, 'Bearer ')) {
                $token = substr($auth, 7);
            }
        }

        if (! $token) {
            return ApiResponse::error('Authentication required', 'UNAUTHORIZED', 401);
        }

        try {
            $decoded = $this->jwt->decode($token);
            $userId = (int) ($decoded->userId ?? 0);
            $user = $this->jwt->loadUserWithRbac($userId);

            if (! $user) {
                return ApiResponse::error('User not found or inactive', 'UNAUTHORIZED', 401);
            }

            $request->attributes->set('auth_user', $user);
            auth()->setUser($user);

            $path = '/'.ltrim(parse_url($request->getRequestUri(), PHP_URL_PATH) ?? '', '/');
            $launchExempt = (bool) preg_match('#/auth/(me|logout|csrf-token)/?$#', $path);

            if (! $launchExempt && ! $user->hasInvoiceLaunchRole()) {
                return ApiResponse::error(
                    'Invoice system only — your role is not enabled for this launch',
                    'LAUNCH_ROLE_FORBIDDEN',
                    403,
                );
            }

            if ($user->isAdmin() && ! $this->isAdminObserverWriteAllowed($request)) {
                return ApiResponse::error(
                    'Admin access is read-only except creating user accounts',
                    'FORBIDDEN',
                    403,
                );
            }

            return $next($request);
        } catch (\Throwable) {
            return ApiResponse::error('Invalid or expired token', 'UNAUTHORIZED', 401);
        }
    }

    private function isAdminObserverWriteAllowed(Request $request): bool
    {
        $method = strtoupper($request->method());
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        $path = '/'.ltrim(parse_url($request->getRequestUri(), PHP_URL_PATH) ?? '', '/');
        $rules = [
            ['POST', '#^/api/v1/users/?$#'],
            ['POST', '#^/api/v1/auth/logout/?$#'],
            ['PATCH', '#^/api/v1/profile/?$#'],
            ['PATCH', '#^/api/v1/profile/password/?$#'],
        ];

        foreach ($rules as [$ruleMethod, $pattern]) {
            if ($method === $ruleMethod && preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}
