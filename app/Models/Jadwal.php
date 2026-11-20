<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    protected $table = 'jadwal';

    protected $fillable = ['id_kursus', 'pertemuan_ke', 'jenis', 'tanggal', 'jam_mulai', 'jam_selesai', 'tanggal_asal', 'keterangan'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'tanggal_asal' => 'date'];
    }

    public function kursus()
    {
        return $this->belongsTo(Kursus::class, 'id_kursus');
    }

    // Satu jadwal memiliki paling banyak satu absensi (R6)
    public function absensi()
    {
        return $this->hasOne(Absensi::class, 'id_jadwal');
    }

    // Riwayat pengajuan ganti hari dari mentor
    public function pengajuanJadwal()
    {
        return $this->hasMany(PengajuanJadwal::class, 'id_jadwal');
    }

    // Pengajuan ganti hari yang masih menunggu persetujuan owner/admin
    public function pengajuanMenunggu(): ?PengajuanJadwal
    {
        return $this->relationLoaded('pengajuanJadwal')
            ? $this->pengajuanJadwal->firstWhere('status', 'Menunggu')
            : $this->pengajuanJadwal()->where('status', 'Menunggu')->first();
    }

    // Pertemuan tambahan (kompensasi tanpa biaya, di luar paket)
    public function tambahan(): bool
    {
        return $this->jenis === 'tambahan';
    }

    // Contoh: "ke-3 dari 12" atau "tambahan (ke-13)"
    public function labelKe(): string
    {
        return $this->tambahan()
            ? "tambahan (ke-{$this->pertemuan_ke})"
            : "ke-{$this->pertemuan_ke} dari {$this->kursus->jumlah_pertemuan}";
    }

    // Pertemuan pengganti (tanggalnya sudah dipindah dari jadwal semula)
    public function pengganti(): bool
    {
        return $this->tanggal_asal !== null;
    }

    // Jadwal masih boleh dipindah: belum diabsen, kursus berjalan, dan honor belum diajukan
    public function bisaDiubah(): bool
    {
        return ! $this->absensi
            && $this->kursus->status === 'diambil' && ! $this->kursus->pengajuanTerakhir();
    }
}
