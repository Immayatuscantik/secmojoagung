<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mentor extends Model
{
    protected $table = 'mentor';

    protected $fillable = [
        'id_user', 'jenis_kelamin', 'tanggal_lahir', 'alamat', 'latitude', 'longitude',
        'pendidikan_terakhir', 'asal_institusi', 'pengalaman_mengajar',
    ];

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date'];
    }

    // Profil mentor dimiliki oleh satu akun
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Bidang yang dikuasai mentor (tanpa duplikat, karena satu bidang bisa punya beberapa tingkat)
    public function bidang()
    {
        return $this->belongsToMany(Bidang::class, 'mentor_bidang', 'id_mentor', 'id_bidang')->distinct();
    }

    // Keahlian mentor: pasangan bidang + tingkat (tabel mentor_bidang)
    public function keahlian()
    {
        return $this->hasMany(KeahlianMentor::class, 'id_mentor');
    }

    // Tingkat yang diajar mentor pada satu bidang, misalnya ['SD', 'SMP']
    public function tingkatUntuk(int $idBidang): array
    {
        return KeahlianMentor::urutkan(
            $this->keahlian->where('id_bidang', $idBidang)->pluck('tingkat')
        );
    }

    // Ringkasan keahlian, misalnya "Mapel Umum (SD, SMP), Sempoa (SD)"
    public function ringkasanKeahlian(): string
    {
        return $this->bidang->map(fn ($b) => $b->nama_bidang . ' (' . implode(', ', $this->tingkatUntuk($b->id)) . ')')
            ->join(', ');
    }

    // Apakah mentor menguasai bidang DAN tingkat murid pada kursus ini
    public function bisaMengajar(Kursus $kursus): bool
    {
        return $this->keahlian->contains(fn ($k) => (int) $k->id_bidang === (int) $kursus->id_bidang
            && $k->tingkat === $kursus->murid->tingkat());
    }

    // Menyimpan ulang keahlian dari isian form: [id_bidang => ['SD', 'SMP'], ...]
    public function simpanKeahlian(array $keahlian): void
    {
        $this->keahlian()->delete();
        foreach ($keahlian as $idBidang => $daftarTingkat) {
            foreach ($daftarTingkat as $tingkat) {
                $this->keahlian()->create(['id_bidang' => $idBidang, 'tingkat' => $tingkat]);
            }
        }
        $this->unsetRelation('keahlian')->unsetRelation('bidang');
    }

    // Kursus yang diajar mentor
    public function kursus()
    {
        return $this->hasMany(Kursus::class, 'id_mentor');
    }

    public function pengajuanHonor()
    {
        return $this->hasMany(PengajuanHonor::class, 'id_mentor');
    }

    public function umur(): int
    {
        return $this->tanggal_lahir->age;
    }
}