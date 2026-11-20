<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Pengajuan ganti hari oleh mentor
class PengajuanJadwal extends Model
{
    protected $table = 'pengajuan_jadwal';

    protected $fillable = [
        'id_jadwal', 'id_mentor', 'tanggal_baru', 'jam_mulai', 'jam_selesai', 'alasan',
        'status', 'catatan_pengelola', 'id_pemroses', 'diproses_pada',
    ];

    protected function casts(): array
    {
        return ['tanggal_baru' => 'date', 'diproses_pada' => 'datetime'];
    }

    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class, 'id_jadwal');
    }

    public function mentor()
    {
        return $this->belongsTo(Mentor::class, 'id_mentor');
    }

    public function pemroses()
    {
        return $this->belongsTo(User::class, 'id_pemroses');
    }

    // Kelas warna label status
    public function kelasLabel(): string
    {
        return match ($this->status) {
            'Disetujui' => 'diambil',
            'Ditolak'   => 'tidak-hadir',
            default     => 'tersedia',
        };
    }

    // Contoh: "Rabu, 20 Nov 2026, 16.00–17.30"
    public function uraianJadwalBaru(): string
    {
        return $this->tanggal_baru->translatedFormat('l, d M Y') . ', '
            . str_replace(':', '.', substr($this->jam_mulai, 0, 5)) . '–' . str_replace(':', '.', substr($this->jam_selesai, 0, 5));
    }
}
