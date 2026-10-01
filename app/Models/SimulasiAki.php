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
        'kategori',
        'paketisasi',
        'bandwidth',
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
    const STATUS_MENUNGGU_PIHAK_B  = 'menunggu_pihak_b';
    const STATUS_LENGKAP_TERHITUNG = 'lengkap_terhitung';
    const STATUS_MENUNGGU_REVIEW   = 'menunggu_review';
    const STATUS_APPROVED          = 'approved';
    const STATUS_REVISI            = 'revisi';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT_PIHAK_B     => 'Draft (Pihak B)',
            self::STATUS_MENUNGGU_PIHAK_A  => 'Menunggu Engineer Development (ED)',
            self::STATUS_MENUNGGU_PIHAK_B  => 'Menunggu Marketing',
            self::STATUS_LENGKAP_TERHITUNG => 'Lengkap & Terhitung',
            self::STATUS_MENUNGGU_REVIEW   => 'Menunggu Review RSO',
            self::STATUS_APPROVED          => 'Disetujui RSO',
            self::STATUS_REVISI            => 'Perlu Revisi',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function getPaketisasiAttribute($value)
    {
        return $value ?? ($this->input_json['paketisasi'] ?? null);
    }

    public function getBandwidthAttribute($value)
    {
        return $value ?? ($this->input_json['bandwidth'] ?? null);
    }

    public function getKategoriAttribute($value)
    {
        return $value ?? ($this->input_json['kategori'] ?? null);
    }

    public static function ensureColumnsExist(): void
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('simulasi_aki')) {
                \Illuminate\Support\Facades\Schema::table('simulasi_aki', function (\Illuminate\Database\Schema\Blueprint $table) {
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('simulasi_aki', 'kategori')) {
                        $table->string('kategori')->nullable()->after('lokasi');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('simulasi_aki', 'paketisasi')) {
                        $table->string('paketisasi')->nullable()->after('kategori');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('simulasi_aki', 'bandwidth')) {
                        $table->string('bandwidth')->nullable()->after('paketisasi');
                    }
                });
            }
        } catch (\Throwable $e) {
            // Ignore
        }
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

    /**
     * User Engineering Development (ED) yang menginput proyek & estimasi CAPEX (pemula simulasi).
     */
    public function getPenginputEdAttribute(): ?User
    {
        return $this->pihakA ?? ($this->status === self::STATUS_MENUNGGU_PIHAK_A ? null : $this->pihakB);
    }

    /**
     * User Marketing yang mengisi / mengupdate parameter komersial (OTC, MRC, Paketisasi, Bandwidth).
     */
    public function getPengisiMarketingAttribute(): ?User
    {
        return $this->pihakB;
    }

    /**
     * Alias user penginput utama (ED).
     */
    public function getPenginputAttribute(): ?User
    {
        return $this->penginputEd;
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
            // Marketing melihat: simulasi yang dia assigned sebagai pihak_b,
            // simulasi yang menunggu mereka lengkapi, atau simulasi yang butuh revisi.
            return $query->where(function ($q) use ($user) {
                $q->where('user_id_pihak_b', $user->id)
                  ->orWhere('status', self::STATUS_MENUNGGU_PIHAK_B)
                  ->orWhere('status', self::STATUS_REVISI);
            });
        }

        if ($user->hasRole('engineering_capex')) {
            // ED melihat: simulasi yang dia buat (user_id_pihak_a)
            // atau simulasi yang dia assigned sebagai pihak_b.
            // STATUS_MENUNGGU_PIHAK_A dihapus — jalur lama sudah tidak aktif.
            return $query->where(function ($q) use ($user) {
                $q->where('user_id_pihak_a', $user->id)
                  ->orWhere('user_id_pihak_b', $user->id);
            });
        }

        return $query->whereRaw('1 = 0'); // fallback: nothing
    }
}
