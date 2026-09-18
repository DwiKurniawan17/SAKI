<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class ParameterRdm extends Model
{
    protected $table = 'parameter_rdm';

    protected $fillable = [
        'rdm_otc_pct',
        'rdm_mrc_pct',
        'bop_pct',
        'lifetime',
        'om_pct',
        'tax_pct',
        'wacc_pct',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'rdm_otc_pct' => 'float',
            'rdm_mrc_pct' => 'float',
            'bop_pct'     => 'float',
            'lifetime'    => 'integer',
            'om_pct'      => 'float',
            'tax_pct'     => 'float',
            'wacc_pct'    => 'float',
        ];
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Dapatkan parameter RDM & Finansial RSO terkini (single row)
     */
    public static function getActive(): self
    {
        self::ensureColumnsExist();

        $record = static::first();
        if (!$record) {
            $record = static::create([
                'rdm_otc_pct' => 25.00,
                'rdm_mrc_pct' => 30.00,
                'bop_pct'     => 40.00,
                'lifetime'    => 4,
                'om_pct'      => 12.00,
                'tax_pct'     => 22.00,
                'wacc_pct'    => 10.90,
            ]);
        }

        return $record;
    }

    /**
     * Pastikan kolom bop_pct, lifetime, om_pct, tax_pct, wacc_pct tersedia di database
     */
    public static function ensureColumnsExist(): void
    {
        try {
            if (Schema::hasTable('parameter_rdm') && !Schema::hasColumn('parameter_rdm', 'bop_pct')) {
                Schema::table('parameter_rdm', function (Blueprint $table) {
                    if (!Schema::hasColumn('parameter_rdm', 'bop_pct')) {
                        $table->decimal('bop_pct', 5, 2)->default(40.00)->nullable();
                    }
                    if (!Schema::hasColumn('parameter_rdm', 'lifetime')) {
                        $table->integer('lifetime')->default(4)->nullable();
                    }
                    if (!Schema::hasColumn('parameter_rdm', 'om_pct')) {
                        $table->decimal('om_pct', 5, 2)->default(12.00)->nullable();
                    }
                    if (!Schema::hasColumn('parameter_rdm', 'tax_pct')) {
                        $table->decimal('tax_pct', 5, 2)->default(22.00)->nullable();
                    }
                    if (!Schema::hasColumn('parameter_rdm', 'wacc_pct')) {
                        $table->decimal('wacc_pct', 5, 2)->default(10.90)->nullable();
                    }
                });
            }
        } catch (\Throwable $e) {
            // Abaikan jika sudah ada atau dalam migrasi
        }
    }

    public function getBopPctAttribute($value)
    {
        return $value !== null ? (float) $value : 40.00;
    }

    public function getLifetimeAttribute($value)
    {
        return $value !== null ? (int) $value : 4;
    }

    public function getOmPctAttribute($value)
    {
        return $value !== null ? (float) $value : 12.00;
    }

    public function getTaxPctAttribute($value)
    {
        return $value !== null ? (float) $value : 22.00;
    }

    public function getWaccPctAttribute($value)
    {
        return $value !== null ? (float) $value : 10.90;
    }
}
