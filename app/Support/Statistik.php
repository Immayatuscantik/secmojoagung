<?php

namespace App\Support;

use App\Http\Controllers\AbsensiController;
use App\Models\Bidang;
use App\Models\Jadwal;
use App\Models\Pencairan;
use Carbon\Carbon;

// Kumpulan perhitungan statistik untuk Dashboard dan Laporan
class Statistik
{
    // Status sebuah jadwal: terpenuhi (sudah diabsen), tidak_absen (waktu absen sudah lewat), atau belum
    public static function statusJadwal(Jadwal $j): string
    {
        if ($j->absensi) {
            return 'terpenuhi';
        }
        $batas = Carbon::parse($j->tanggal->toDateString() . ' ' . $j->jam_mulai)
            ->addMinutes(AbsensiController::TUTUP_SESUDAH);

        return now()->gt($batas) ? 'tidak_absen' : 'belum';
    }

    // Persentase dengan satu angka desimal; 0 jika pembagi 0
    public static function persen(int $bagian, int $total): float
    {
        return $total > 0 ? round($bagian / $total * 100, 1) : 0;
    }

    // Rekap pertemuan pada rentang tanggal: total dan per mentor
    public static function pertemuan(Carbon $awal, Carbon $akhir): array
    {
        $jadwal = Jadwal::whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->whereHas('kursus', fn ($q) => $q->whereNotNull('id_mentor'))
            ->with(['kursus.mentor.user', 'absensi'])
            ->get()
            ->each(fn ($j) => $j->status_rekap = self::statusJadwal($j));

        $hitung = fn ($daftar, $status) => $daftar->where('status_rekap', $status)->count();

        $rekap = [
            'target'      => $jadwal->count(),
            'terpenuhi'   => $hitung($jadwal, 'terpenuhi'),
            'tidak_absen' => $hitung($jadwal, 'tidak_absen'),
            'belum'       => $hitung($jadwal, 'belum'),
        ];
        $rekap['persen'] = self::persen($rekap['terpenuhi'], $rekap['target']);

        $rekap['perMentor'] = $jadwal->groupBy(fn ($j) => $j->kursus->id_mentor)
            ->map(fn ($daftar) => [
                'nama'        => $daftar->first()->kursus->mentor->user->nama,
                'target'      => $daftar->count(),
                'terpenuhi'   => $hitung($daftar, 'terpenuhi'),
                'tidak_absen' => $hitung($daftar, 'tidak_absen'),
                'persen'      => self::persen($hitung($daftar, 'terpenuhi'), $daftar->count()),
            ])
            ->sortBy('nama')->values();

        return $rekap;
    }

    // Total honor dicairkan per bulan selama $jumlahBulan bulan sampai bulan $bulanAkhir
    public static function honorBulanan(Carbon $bulanAkhir, int $jumlahBulan = 6): array
    {
        $awal  = $bulanAkhir->copy()->startOfMonth()->subMonths($jumlahBulan - 1);
        $akhir = $bulanAkhir->copy()->endOfMonth();

        $perBulan = Pencairan::whereBetween('tanggal_pencairan', [$awal->toDateString(), $akhir->toDateString()])
            ->get()
            ->groupBy(fn ($c) => $c->tanggal_pencairan->format('Y-m'))
            ->map(fn ($daftar) => $daftar->sum('nominal'));

        $hasil = ['label' => [], 'nilai' => []];
        for ($b = $awal->copy(); $b->lte($akhir); $b->addMonth()) {
            $hasil['label'][] = $b->translatedFormat('M Y');
            $hasil['nilai'][] = (int) ($perBulan[$b->format('Y-m')] ?? 0);
        }

        return $hasil;
    }

    // Jumlah kursus per bidang (minat kursus), dipisah: sedang berjalan dan mencari mentor
    public static function kursusPerBidang(): array
    {
        $bidang = Bidang::withCount([
            'kursus as berjalan'       => fn ($q) => $q->where('status', 'diambil'),
            'kursus as mencari_mentor' => fn ($q) => $q->whereIn('status', ['menunggu', 'tersedia']),
        ])->orderBy('nama_bidang')->get();

        return [
            'label'          => $bidang->pluck('nama_bidang')->all(),
            'berjalan'       => $bidang->pluck('berjalan')->all(),
            'mencari_mentor' => $bidang->pluck('mencari_mentor')->all(),
        ];
    }
}
