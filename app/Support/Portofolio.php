<?php

namespace App\Support;

use App\Models\Absensi;
use App\Models\Kursus;
use App\Models\Mentor;
use Carbon\Carbon;
use Illuminate\Http\Request;

// Portofolio mentor: rekam jejak mengajar, murid yang pernah diajar, dan honor yang diterima.
// Dipakai di menu Portofolio (mentor) dan tab Portofolio pada Detail Mentor (owner/admin).
class Portofolio
{
    const BAGIAN = ['ringkasan', 'murid', 'absensi', 'honor'];

    public static function data(Mentor $mentor, Request $request): array
    {
        $request->validate([
            'bagian' => 'nullable|in:' . implode(',', self::BAGIAN),
            'bulan'  => 'nullable|date_format:Y-m',
            'status' => 'nullable|in:Valid,Perlu Ditinjau',
        ]);
        $bagian = $request->bagian ?? 'ringkasan';

        // ===== Semua kursus yang pernah/sedang diajar mentor =====
        $kursus = Kursus::where('id_mentor', $mentor->id)
            ->with(['murid.wali.user', 'bidang', 'jadwal.absensi', 'pengajuanHonor.pencairan', 'verifikator'])
            ->get()
            ->each(function ($k) {
                $k->hadir_count = $k->jadwal->filter(fn ($j) => ! $j->tambahan() && $j->absensi)->count();
                $k->tidak_absen = $k->jadwal->filter(fn ($j) => Statistik::statusJadwal($j) === 'tidak_absen')->count();
                $k->diganti     = $k->jadwal->whereNotNull('tanggal_asal')->count();
                $k->persen_hadir = Statistik::persen($k->hadir_count, $k->hadir_count + $k->tidak_absen);
                $k->persen_progres = Statistik::persen($k->hadir_count, $k->jumlah_pertemuan);
                $k->mulai = $k->jadwal->min('tanggal');
                $k->akhir = $k->jadwal->max('tanggal');
            })
            ->sortByDesc('mulai')
            ->values();

        $absensiSemua = $kursus->flatMap(fn ($k) => $k->jadwal->pluck('absensi')->filter());
        $hadir      = $kursus->sum('hadir_count');
        $tidakAbsen = $kursus->sum('tidak_absen');
        $lengkap    = $kursus->filter(fn ($k) => $k->pertemuanLengkap());

        // ===== Honor: kursus yang pertemuannya sudah lengkap =====
        $honor = $lengkap->map(function ($k) {
            $p = $k->pengajuanTerakhir();
            $k->pengajuan_terakhir = $p;
            $k->nominal_honor = $p?->pencairan?->nominal ?? $k->honor;
            return $k;
        })->sortByDesc('akhir')->values();

        $statusHonor = fn ($k) => $k->pengajuan_terakhir?->status;
        $ringkasan = [
            'kursus'          => $kursus->count(),
            'kursus_selesai'  => $lengkap->count(),
            'kursus_berjalan' => $kursus->count() - $lengkap->count(),
            'murid'           => $kursus->pluck('id_murid')->unique()->count(),
            'pertemuan'       => $hadir,
            'tidak_absen'     => $tidakAbsen,
            'diganti'         => $kursus->sum('diganti'),
            'persen_hadir'    => Statistik::persen($hadir, $hadir + $tidakAbsen),
            'valid'           => $absensiSemua->where('status', 'Valid')->count(),
            'tinjau'          => $absensiSemua->where('status', 'Perlu Ditinjau')->count(),
            'mulai_mengajar'  => $absensiSemua->min('waktu_absen'),
            'diterima'        => $honor->filter(fn ($k) => $statusHonor($k) === 'Dicairkan')->sum('nominal_honor'),
            'jumlah_cair'     => $honor->filter(fn ($k) => $statusHonor($k) === 'Dicairkan')->count(),
            'diproses'        => $honor->filter(fn ($k) => in_array($statusHonor($k), ['Diajukan', 'Disetujui']))->sum('nominal_honor'),
            'belum_diajukan'  => $honor->filter(fn ($k) => in_array($statusHonor($k), [null, 'Dikembalikan']))->sum('nominal_honor'),
        ];
        $ringkasan['persen_valid'] = Statistik::persen($ringkasan['valid'], $absensiSemua->count());

        // ===== Rekap per bidang =====
        $perBidang = $kursus->groupBy(fn ($k) => $k->bidang->nama_bidang)->map(fn ($d) => [
            'kursus'    => $d->count(),
            'murid'     => $d->pluck('id_murid')->unique()->count(),
            'pertemuan' => $d->sum('hadir_count'),
            'persen'    => Statistik::persen($d->sum('hadir_count'), $d->sum('hadir_count') + $d->sum('tidak_absen')),
        ])->sortKeys();

        // ===== Aktivitas 6 bulan terakhir =====
        $aktivitas = collect();
        for ($b = now()->startOfMonth()->subMonths(5); $b->lte(now()); $b->addMonth()) {
            $kode = $b->format('Y-m');
            $aktivitas->push([
                'label'     => $b->translatedFormat('F Y'),
                'pertemuan' => $absensiSemua->filter(fn ($a) => $a->waktu_absen->format('Y-m') === $kode)->count(),
                'honor'     => $honor->filter(fn ($k) => $statusHonor($k) === 'Dicairkan'
                    && $k->pengajuan_terakhir->pencairan?->tanggal_pencairan?->format('Y-m') === $kode)->sum('nominal_honor'),
            ]);
        }

        // ===== Riwayat absensi (bisa difilter bulan & status) =====
        $absensi = Absensi::whereHas('jadwal.kursus', fn ($q) => $q->where('id_mentor', $mentor->id))
            ->with(['jadwal.kursus.murid', 'jadwal.kursus.bidang', 'jadwal.kursus.pengajuanHonor', 'riwayatCatatan'])
            ->when($request->bulan, function ($q, $bulan) {
                $awal = Carbon::createFromFormat('Y-m-d', $bulan . '-01')->startOfMonth();
                $q->whereBetween('waktu_absen', [$awal, $awal->copy()->endOfMonth()]);
            })
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('waktu_absen')
            ->paginate(15)
            ->withQueryString();

        return compact('bagian', 'kursus', 'ringkasan', 'perBidang', 'aktivitas', 'honor', 'absensi');
    }
}
