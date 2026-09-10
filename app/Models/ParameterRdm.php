<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParameterRdm extends Model
{
    protected $table = 'parameter_rdm';

    protected $fillable = [
        'rdm_otc_pct',
        'rdm_mrc_pct',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'rdm_otc_pct' => 'float',
            'rdm_mrc_pct' => 'float',
        ];
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Dapatkan parameter RDM terkini (single row)
     */
    public static function getActive(): self
    {
        return static::firstOrCreate([], [
            'rdm_otc_pct' => 25.00,
            'rdm_mrc_pct' => 30.00,
        ]);
    }
}
