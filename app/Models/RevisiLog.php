<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevisiLog extends Model
{
    protected $table = 'revisi_log';

    protected $fillable = [
        'simulasi_id',
        'requested_by',
        'catatan',
    ];

    public function simulasi()
    {
        return $this->belongsTo(SimulasiAki::class, 'simulasi_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
