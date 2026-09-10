<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force HTTPS in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Rate limiting: login — max 5 attempts per minute per email+IP
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = strtolower($request->input('email')) . '|' . $request->ip();
            return Limit::perMinute(5)->by($throttleKey);
        });

        // Rate limiting: register — max 3 attempts per minute per IP
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });
    }
}
