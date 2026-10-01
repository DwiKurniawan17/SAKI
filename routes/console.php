<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('user:disable-2fa {email}', function ($email) {
    $user = \App\Models\User::where('email', $email)->first();
    if (! $user) {
        $this->error("User {$email} tidak ditemukan.");
        return 1;
    }

    $user->forceFill([
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
        'two_factor_confirmed_at' => null,
    ])->save();

    $this->info("2FA berhasil dinonaktifkan untuk: {$user->name} ({$user->email})");
    $this->info("Role: " . implode(', ', $user->getRoleNames()->toArray()));
    return 0;
})->purpose('Nonaktifkan 2FA untuk user tertentu');

