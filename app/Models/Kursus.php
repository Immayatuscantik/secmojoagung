<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Kursus extends Model
{
    protected $table = 'kursus';

    protected $fillable = [
        'id_murid', 'id_bidang', 'id_mentor', 'jumlah_pertemuan', 'honor',
        'status', 'verifikasi_wali', 'alasan_wali', 'tanggal_verifikasi', 'id_verifikator',
    ];

    protected function casts(): array
    {
        return ['tanggal_verifikasi' => 'datetime'];
    }

    const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public function murid()
    {
        return $this->belongsTo(Murid::class, 'id_murid');
    }

    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'id_bidang');
    }

    // Kursus diajar oleh paling banyak satu mentor (R1)
    public function mentor()
    {
        return $this->belongsTo(Mentor::class, 'id_mentor');
    }

    public function jadwal()
    {
        return $this->hasMany(Jadwal::class, 'id_kursus');
    }

    public function pengajuanHonor()
    {
        return $this->hasMany(PengajuanHonor::class, 'id_kursus');
    }

    // Akun yang memverifikasi pertemuan (wali sendiri, atau admin/owner atas nama wali)
    public function verifikator()
    {
        return $this->belongsTo(User::class, 'id_verifikator');
    }

    // Diskusi verifikasi pertemuan (keberatan wali, tanggapan mentor, penengahan owner/admin)
    public function tanggapan()
    {
        return $this->hasMany(TanggapanVerifikasi::class, 'id_kursus')->orderBy('created_at')->orderBy('id');
    }

    // Diskusi masih terbuka: wali menyatakan belum sesuai dan belum menyetujui
    public function diskusiTerbuka(): bool
    {
        return $this->verifikasi_wali === 'belum_sesuai';
    }

    // Pernah ada keberatan wali (diskusi ditampilkan, termasuk yang sudah selesai)
    public function adaDiskusi(): bool
    {
        return $this->diskusiTerbuka() || $this->tanggapan->contains('jenis', 'keberatan');
    }

    // Membuat jadwal otomatis mulai $tanggalMulai pada hari-hari terpilih (1 = Senin ... 6 = Sabtu)
    public function buatJadwal(string $tanggalMulai, array $hari, string $jamMulai, string $jamSelesai): void
    {
        $tanggal = Carbon::parse($tanggalMulai);
        $ke = 1;

        while ($ke <= $this->jumlah_pertemuan) {
            if (in_array($tanggal->dayOfWeekIso, $hari)) {
                $this->jadwal()->create([
                    'pertemuan_ke' => $ke,
                    'tanggal'      => $tanggal->toDateString(),
                    'jam_mulai'    => $jamMulai,
                    'jam_selesai'  => $jamSelesai,
                ]);
                $ke++;
            }
            $tanggal->addDay();
        }
    }

    // Teks dan warna label status kursus
    public function labelStatus(): array
    {
        return match (true) {
            $this->status === 'menunggu' => ['Menunggu konfirmasi', 's-diajukan'],
            $this->status === 'tersedia' => ['Mencari mentor', 'tersedia'],
            $this->pertemuanLengkap()    => ['Selesai', 's-dicairkan'],
            default                      => ['Berjalan', 'diambil'],
        };
    }

    // Teks dan warna label verifikasi wali
    public function labelVerifikasi(): array
    {
        $olehPengelola = $this->verifikator && $this->verifikator->peran !== 'wali';

        return match ($this->verifikasi_wali) {
            'disetujui'    => [$olehPengelola ? 'Disetujui admin atas nama wali' : 'Disetujui wali', 'diambil'],
            'belum_sesuai' => ['Belum sesuai', 'tidak-hadir'],
            default        => ['Belum diverifikasi', ''],
        };
    }

    // Status honor mentor untuk kursus ini: [teks, kelas label]
    public function statusHonor(): array
    {
        if (! $this->pertemuanLengkap()) {
            return ['Pertemuan belum lengkap', ''];
        }
        $p = $this->pengajuanTerakhir();
        if (! $p) {
            return $this->verifikasi_wali === 'disetujui'
                ? ['Belum diajukan mentor', 'tersedia']
                : ['Menunggu verifikasi wali', 'tersedia'];
        }

        return [$p->status, 's-' . strtolower($p->status)];
    }

    // Pengajuan honor terakhir untuk kursus ini
    public function pengajuanTerakhir(): ?PengajuanHonor
    {
        return $this->relationLoaded('pengajuanHonor')
            ? $this->pengajuanHonor->sortByDesc('id')->first()
            : $this->pengajuanHonor()->latest('id')->first();
    }

    // Jumlah pertemuan yang sudah diabsen
    public function jumlahHadir(): int
    {
        // Hanya pertemuan rutin (paket); memakai hasil withCount('hadir_count') jika sudah dimuat
        if (isset($this->hadir_count)) {
            return (int) $this->hadir_count;
        }

        return $this->relationLoaded('jadwal')
            ? $this->jadwal->filter(fn ($j) => $j->jenis !== 'tambahan' && $j->absensi)->count()
            : $this->jadwal()->where('jenis', 'rutin')->has('absensi')->count();
    }

    // Pertemuan tambahan yang belum dilaksanakan (honor baru dapat diajukan setelah semuanya diabsen)
    public function tambahanBelumSelesai()
    {
        return $this->relationLoaded('jadwal')
            ? $this->jadwal->filter(fn ($j) => $j->jenis === 'tambahan' && ! $j->absensi)->values()
            : $this->jadwal()->where('jenis', 'tambahan')->doesntHave('absensi')->get();
    }

    // Seluruh pertemuan dalam paket sudah diabsen
    public function pertemuanLengkap(): bool
    {
        return $this->jumlahHadir() >= $this->jumlah_pertemuan;
    }

    // Jadwal rutin (tanpa pertemuan pengganti dan pertemuan tambahan)
    private function jadwalPokok()
    {
        $pokok = $this->jadwal->filter(fn ($j) => ! $j->tanggal_asal && $j->jenis !== 'tambahan');

        return $pokok->isEmpty() ? $this->jadwal : $pokok;
    }

    // Nama hari belajar, misalnya "Senin dan Kamis"
    public function hariBelajar(): string
    {
        $hari = $this->jadwalPokok()
            ->map(fn ($j) => $j->tanggal->dayOfWeekIso)
            ->unique()->sort()
            ->map(fn ($h) => self::HARI[$h])
            ->values()->all();

        if (count($hari) <= 1) {
            return $hari[0] ?? '-';
        }

        return implode(', ', array_slice($hari, 0, -1)) . ' dan ' . end($hari);
    }

    // Jam belajar, misalnya "16.00–17.30"
    public function jamBelajar(): string
    {
        $j = $this->jadwalPokok()->first();

        return $j
            ? str_replace(':', '.', substr($j->jam_mulai, 0, 5)) . '–' . str_replace(':', '.', substr($j->jam_selesai, 0, 5))
            : '-';
    }
}
