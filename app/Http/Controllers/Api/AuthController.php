<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private JwtService $jwt) {}

    public function csrfToken(Request $request): JsonResponse
    {
        $token = bin2hex(random_bytes(32));
        $opts = $this->jwt->csrfCookieOptions();

        $response = ApiResponse::success(['csrfToken' => $token]);
        $response->headers->setCookie(cookie(
            'sns_csrf',
            $token,
            $opts['max_age'] ?? 86400,
            $opts['path'] ?? '/',
            null,
            $opts['secure'] ?? false,
            $opts['httponly'] ?? false,
            false,
            $opts['samesite'] ?? 'lax',
        ));

        return $response;
    }

    public function login(Request $request): JsonResponse
    {
        $identifier = (string) ($request->input('login')
            ?? $request->input('username')
            ?? $request->input('email')
            ?? '');
        $password = (string) $request->input('password', '');
        $rememberMe = (bool) $request->boolean('remember_me');

        $identifier = strtolower(ltrim(trim($identifier), '@'));

        if ($identifier === '' || $password === '') {
            return ApiResponse::error(
                'Email or username and password are required',
                'INVALID_INPUT',
                400,
            );
        }

        $user = $this->jwt->findUserByIdentifier($identifier);
        if (! $user || ! $this->jwt->verifyPassword($user, $password)) {
            return ApiResponse::error('Invalid email/username or password', 'INVALID_CREDENTIALS', 401);
        }

        if (! $user->is_active) {
            return ApiResponse::error('User account is deactivated', 'ACCOUNT_DISABLED', 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $user = $this->jwt->loadUserWithRbac((int) $user->id);
        if (! $user) {
            return ApiResponse::error('User not found or inactive', 'UNAUTHORIZED', 401);
        }

        $token = $this->jwt->issue($user, $rememberMe);
        $opts = $this->jwt->sessionCookieOptions($rememberMe);

        $response = ApiResponse::success([
            'user' => array_merge($user->toAuthUserArray(), [
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]),
            'roles' => $user->rolesArray(),
            'permissions' => $user->permissionPairs(),
            'token' => $token,
        ]);

        $response->headers->setCookie(cookie(
            'sns_token',
            $token,
            $opts['max_age'] ?? null,
            $opts['path'] ?? '/',
            null,
            $opts['secure'] ?? false,
            $opts['httponly'] ?? true,
            false,
            $opts['samesite'] ?? 'lax',
        ));

        return $response;
    }

    public function logout(Request $request): JsonResponse
    {
        $opts = $this->jwt->sessionCookieOptions(false);
        $csrfOpts = $this->jwt->csrfCookieOptions();

        $response = ApiResponse::success(['message' => 'Logged out successfully']);
        $response->headers->setCookie(cookie()->forget('sns_token', $opts['path'] ?? '/', null));
        $response->headers->setCookie(cookie()->forget('sns_csrf', $csrfOpts['path'] ?? '/', null));

        return $response;
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        if (! $user) {
            return ApiResponse::error('Not authenticated', 'UNAUTHORIZED', 401);
        }

        return ApiResponse::success([
            'user' => $user->toAuthUserArray(),
            'roles' => $user->rolesArray(),
            'permissions' => $user->permissionPairs(),
        ]);
    }
}
