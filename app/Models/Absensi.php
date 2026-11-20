<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $table = 'absensi';

    protected $fillable = [
        'id_jadwal', 'waktu_absen', 'latitude', 'longitude',
        'jarak', 'foto', 'catatan_materi', 'status',
    ];

    protected function casts(): array
    {
        return ['waktu_absen' => 'datetime'];
    }

    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class, 'id_jadwal');
    }

    // Riwayat perubahan catatan materi, terbaru di atas
    public function riwayatCatatan()
    {
        return $this->hasMany(RiwayatCatatan::class, 'id_absensi')->latest();
    }
}