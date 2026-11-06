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
        // Force HTTPS in production (except on localhost or in CLI)
        if ($this->app->environment('production') && !$this->app->runningInConsole()) {
            if (!in_array(request()->getHost(), ['localhost', '127.0.0.1'])) {
                URL::forceScheme('https');
            }
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

        // Kustomisasi pesan email Reset Password dengan branding Telkom
        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('[Simulasi AKI/CAPEX] Atur Ulang Kata Sandi Akun Anda')
                ->greeting('Halo, ' . $notifiable->name . '!')
                ->line('Kami menerima permintaan pengaturan ulang kata sandi untuk akun Simulasi AKI/CAPEX Telkom Anda.')
                ->action('Atur Ulang Kata Sandi Sekarang', $url)
                ->line('Tautan atur ulang kata sandi ini hanya berlaku selama 60 menit.')
                ->line('Jika Anda tidak pernah meminta pengaturan ulang kata sandi, abaikan email ini dan akun Anda akan tetap aman.')
                ->salutation('Salam hormat,' . "\n" . 'Tim Simulasi AKI/CAPEX Telkom');
        });
    }
}
