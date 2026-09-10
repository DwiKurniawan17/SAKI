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
     * Cek apakah user ini wajib mengaktifkan 2FA
     */
    public function mustEnable2FA(): bool
    {
        return false;
    }

    /**
     * Cek apakah 2FA sudah aktif
     */
    public function has2FAEnabled(): bool
    {
        return ! is_null($this->two_factor_secret);
    }
}
