<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens, TwoFactorAuthenticatable, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_approved',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_approved' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'is_approved'])
            ->logOnlyDirty()
            ->useLogName('user');
    }

    /**
     * Simulasi yang dibuat sebagai Pihak B
     */
    public function simulasiSebagaiPihakB()
    {
        return $this->hasMany(SimulasiAki::class, 'user_id_pihak_b');
    }

    /**
     * Simulasi yang dilengkapi sebagai Pihak A
     */
    public function simulasiSebagaiPihakA()
    {
        return $this->hasMany(SimulasiAki::class, 'user_id_pihak_a');
    }

    /**
     * Cek apakah user ini wajib mengaktifkan 2FA.
     * Admin (RSO) dan Engineering Capex wajib karena mengelola data keuangan sensitif.
     */
    public function mustEnable2FA(): bool
    {
        // Sementara nonaktif untuk testing
        return false;

        // return $this->hasAnyRole(['admin', 'engineering_capex']);
    }

    /**
     * Cek apakah 2FA sudah aktif dan terkonfirmasi.
     */
    public function has2FAEnabled(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * Cek apakah 2FA sedang dalam proses konfigurasi (secret dibuat tapi belum dikonfirmasi kode OTP).
     */
    public function hasPending2FA(): bool
    {
        return ! is_null($this->two_factor_secret) && is_null($this->two_factor_confirmed_at);
    }

    /**
     * Label human-readable untuk role user
     */
    public function getRoleDisplayNameAttribute(): string
    {
        $role = $this->getRoleNames()->first();
        return match ($role) {
            'engineering_capex' => 'Engineer Development',
            'sales_marketing'   => 'Sales Marketing',
            'admin'             => 'RSO (Admin)',
            default             => $role ? ucwords(str_replace('_', ' ', $role)) : 'User',
        };
    }
}

