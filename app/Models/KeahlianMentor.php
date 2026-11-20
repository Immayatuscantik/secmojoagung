<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Satu baris keahlian mentor: bidang + tingkat yang sanggup diajar (tabel mentor_bidang)
class KeahlianMentor extends Model
{
    protected $table = 'mentor_bidang';

    protected $fillable = ['id_mentor', 'id_bidang', 'tingkat'];

    const TINGKAT = ['TK', 'SD', 'SMP', 'SMA'];

    public function mentor()
    {
        return $this->belongsTo(Mentor::class, 'id_mentor');
    }

    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'id_bidang');
    }

    // Mengurutkan daftar tingkat sesuai urutan TK, SD, SMP, SMA
    public static function urutkan($tingkat): array
    {
        return collect($tingkat)->unique()
            ->sortBy(fn ($t) => array_search($t, self::TINGKAT))
            ->values()->all();
    }
}