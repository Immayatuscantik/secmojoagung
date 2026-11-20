<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatCatatan extends Model
{
    protected $table = 'riwayat_catatan';

    protected $fillable = ['id_absensi', 'catatan_lama', 'catatan_baru'];

    public function absensi()
    {
        return $this->belongsTo(Absensi::class, 'id_absensi');
    }
}