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
        // Bypass sementara untuk testing
        return $next($request);

        $user = $request->user();

        // Hanya berlaku untuk user yang sudah login
        if (! $user) {
            return $next($request);
        }

        // Cek apakah role user wajib 2FA
        if ($user->mustEnable2FA() && ! $user->has2FAEnabled()) {
            // Izinkan akses ke halaman 2FA setup, profile security, dan logout agar tidak loop redirect
            if (
                $request->is('profile/security*') ||
                $request->routeIs('profile.security*') ||
                $request->is('user/two-factor*') ||
                $request->is('user/confirmed-two-factor*') ||
                $request->is('two-factor*') ||
                $request->is('logout')
            ) {
                return $next($request);
            }

            return redirect()->route('profile.security')
                ->with('warning', 'Role Anda (' . $user->role_display_name . ') wajib mengaktifkan Two-Factor Authentication (Google Authenticator) demi keamanan data finansial sebelum dapat mengakses fitur sistem.');
        }

        return $next($request);
    }
}
