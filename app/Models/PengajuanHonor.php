<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanHonor extends Model
{
    protected $table = 'pengajuan_honor';

    protected $fillable = [
        'id_mentor', 'id_kursus', 'tanggal_pengajuan',
        'video', 'alasan_pengembalian', 'status',
    ];

    protected function casts(): array
    {
        return ['tanggal_pengajuan' => 'date'];
    }

    public function mentor()
    {
        return $this->belongsTo(Mentor::class, 'id_mentor');
    }

    public function kursus()
    {
        return $this->belongsTo(Kursus::class, 'id_kursus');
    }

    // Satu pengajuan memiliki paling banyak satu pencairan (R11)
    public function pencairan()
    {
        return $this->hasOne(Pencairan::class, 'id_pengajuan');
    }
}