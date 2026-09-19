<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebInvoiceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->hasInvoiceLaunchRole()) {
            abort(403, 'Invoice system only — your role is not enabled for this launch');
        }

        return $next($request);
    }
}
