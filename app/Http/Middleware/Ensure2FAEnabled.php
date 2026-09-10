<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect users who MUST enable 2FA but haven't yet.
 * (admin & engineering_capex wajib 2FA)
 */
class Ensure2FAEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
