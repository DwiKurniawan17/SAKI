<?php

use App\Http\Middleware\Ensure2FAEnabled;
use App\Http\Middleware\EnsureApproved;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global HTTP security headers
        $middleware->append(SecurityHeaders::class);

        // Named middleware aliases
        $middleware->alias([
            'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'approved'   => EnsureApproved::class,
            'ensure2fa'  => Ensure2FAEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'CSRF token mismatch / Session expired.'], 419);
            }
            return redirect()->route('login')->with('status', 'Sesi Anda telah berakhir. Silakan login kembali.');
        });
    })->create();
