<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SimulasiAki extends Model
{
    use LogsActivity;

    protected $table = 'simulasi_aki';

    protected $fillable = [
        'user_id_pihak_b',
        'user_id_pihak_a',
        'reviewed_by',
        'reviewed_at',
        'status',
        'nama_proyek',
        'lokasi',
        'input_json',
        'hasil_json',
        'npv',
        'mirr',
        'payback_period',
        'layak',
        'catatan_revisi',
    ];

    /**
     * Kolom input_json dan hasil_json dienkripsi at-rest di database
     * (Laravel encrypted cast) — sesuai persyaratan keamanan data finansial.
     */
    protected function casts(): array
    {
        return [
            'input_json'     => 'encrypted:array',
            'hasil_json'     => 'encrypted:array',
            'layak'          => 'boolean',
            'npv'            => 'float',
            'mirr'           => 'float',
            'payback_period' => 'float',
            'reviewed_at'    => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'nama_proyek', 'layak'])
            ->logOnlyDirty()
            ->useLogName('simulasi');
    }

    // ---- Status constants ----
    const STATUS_DRAFT_PIHAK_B     = 'draft_pihak_b';
    const STATUS_MENUNGGU_PIHAK_A  = 'menunggu_pihak_a';
    const STATUS_LENGKAP_TERHITUNG = 'lengkap_terhitung';
    const STATUS_MENUNGGU_REVIEW   = 'menunggu_review';
    const STATUS_APPROVED          = 'approved';
    const STATUS_REVISI            = 'revisi';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT_PIHAK_B     => 'Draft (Pihak B)',
            self::STATUS_MENUNGGU_PIHAK_A  => 'Menunggu Pihak A',
            self::STATUS_LENGKAP_TERHITUNG => 'Lengkap & Terhitung',
            self::STATUS_MENUNGGU_REVIEW   => 'Menunggu Review RSO',
            self::STATUS_APPROVED          => 'Disetujui RSO ✅',
            self::STATUS_REVISI            => 'Perlu Revisi ⚠️',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    // ---- Relationships ----
    public function pihakB()
    {
        return $this->belongsTo(User::class, 'user_id_pihak_b');
    }

    public function pihakA()
    {
        return $this->belongsTo(User::class, 'user_id_pihak_a');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function revisiLogs()
    {
        return $this->hasMany(RevisiLog::class, 'simulasi_id')->latest();
    }

    // ---- Scopes ----
    public function scopeForUser($query, User $user)
    {
        if ($user->hasRole('admin')) {
            return $query; // admin/RSO sees all
        }

        if ($user->hasRole('sales_marketing')) {
            return $query->where('user_id_pihak_b', $user->id);
        }

        if ($user->hasRole('engineering_capex')) {
            return $query->where(function ($q) use ($user) {
                $q->where('user_id_pihak_a', $user->id)
                  ->orWhere('user_id_pihak_b', $user->id)
                  ->orWhere('status', self::STATUS_MENUNGGU_PIHAK_A);
            });
        }

        return $query->whereRaw('1 = 0'); // fallback: nothing
    }
}
