<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bidang extends Model
{
    protected $table = 'bidang';

    protected $fillable = ['nama_bidang', 'keterangan'];

    // Mentor yang menguasai bidang ini
    public function mentor()
    {
        return $this->belongsToMany(Mentor::class, 'mentor_bidang', 'id_bidang', 'id_mentor')->distinct();
    }

    public function kursus()
    {
        return $this->hasMany(Kursus::class, 'id_bidang');
    }

    public function tarifHonor()
    {
        return $this->hasMany(TarifHonor::class, 'id_bidang');
    }

    // Tingkat yang tersedia untuk bidang ini, diambil dari tarif honor.
    // Tarif bertingkat "Semua" berarti tersedia untuk TK, SD, SMP, dan SMA.
    public function tingkatTersedia(): array
    {
        $tingkat = $this->tarifHonor->pluck('tingkat');

        return $tingkat->contains('Semua')
            ? KeahlianMentor::TINGKAT
            : KeahlianMentor::urutkan($tingkat);
    }
}