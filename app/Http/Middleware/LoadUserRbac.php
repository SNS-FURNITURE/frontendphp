<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoadUserRbac
{
    public function __construct(private JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->relationLoaded('roles')) {
            $fresh = $this->jwt->loadUserWithRbac((int) $user->id);
            if ($fresh) {
                auth()->setUser($fresh);
            }
        }

        return $next($request);
    }
}
