<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Port of sns-erp-backend csrfProtectionMiddleware for cookie-authenticated API writes.
 */
class VerifyApiCsrf
{
    public function handle(Request $request, Closure $next): Response
    {
        $method = strtoupper($request->method());
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $path = '/'.ltrim(parse_url($request->getRequestUri(), PHP_URL_PATH) ?? '', '/');
        $exempt = [
            '/api/v1/auth/login',
            '/api/v1/health',
            '/health',
        ];
        if (in_array($path, $exempt, true)) {
            return $next($request);
        }

        if (! $request->cookies->has('sns_token')) {
            return $next($request);
        }

        $requestedWith = $request->header('X-Requested-With');
        $csrfToken = $request->header('X-CSRF-Token');
        $origin = $request->headers->get('Origin');
        $allowed = array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))));

        $hasValidHeader =
            $requestedWith === 'XMLHttpRequest'
            || (bool) $csrfToken
            || ($origin && in_array($origin, $allowed, true));

        if (! $hasValidHeader) {
            return ApiResponse::error(
                'CSRF verification failed: Missing required security header on state-changing request.',
                'CSRF_VERIFICATION_FAILED',
                403,
            );
        }

        return $next($request);
    }
}
