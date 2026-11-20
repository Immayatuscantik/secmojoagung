<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Kursus;
use App\Models\Mentor;
use App\Models\PengajuanJadwal;
use App\Support\Statistik;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // Dashboard monitoring owner/admin
    public function index(Request $request)
    {
        $request->validate([
            'status'  => 'nullable|in:Valid,Perlu Ditinjau',
            'tanggal' => 'nullable|date',
        ]);

        // Ringkasan data
        $stat = [
            'mentor'         => Mentor::count(),
            'kursus_aktif'   => Kursus::where('status', 'diambil')->count(),
            'job_tersedia'   => Kursus::where('status', 'tersedia')->count(),
            'menunggu'       => Kursus::where('status', 'menunggu')->count(),
            'absen_hari_ini' => Absensi::whereDate('waktu_absen', today())->count(),
            'perlu_ditinjau' => Absensi::where('status', 'Perlu Ditinjau')->count(),
        ];

        // Jadwal mengajar hari ini (hanya kursus yang sudah memiliki mentor)
        $jadwalHariIni = Jadwal::whereDate('tanggal', today())
            ->whereHas('kursus', fn ($q) => $q->whereNotNull('id_mentor'))
            ->with(['kursus.murid', 'kursus.bidang', 'kursus.mentor.user', 'absensi'])
            ->orderBy('jam_mulai')
            ->get();

        // Riwayat absensi dengan filter status dan tanggal
        $absensi = Absensi::with(['jadwal.kursus.murid', 'jadwal.kursus.bidang', 'jadwal.kursus.mentor.user'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->tanggal, fn ($q, $t) => $q->whereDate('waktu_absen', $t))
            ->latest('waktu_absen')
            ->paginate(10)
            ->withQueryString();

        // Grafik: pertemuan bulan ini, honor 6 bulan terakhir, dan kursus per bidang
        $bulanIni = Statistik::pertemuan(now()->startOfMonth(), now()->endOfMonth());
        $grafik = [
            'pertemuan' => $bulanIni,
            'honor'     => Statistik::honorBulanan(now()),
            'bidang'    => Statistik::kursusPerBidang(),
        ];

        // Perlu tindak lanjut: keberatan wali (diskusi verifikasi) dan pengajuan ganti jadwal dari mentor
        $keberatan = Kursus::where('verifikasi_wali', 'belum_sesuai')
            ->with(['murid.wali.user', 'bidang', 'mentor.user', 'tanggapan.user'])
            ->orderByDesc('tanggal_verifikasi')
            ->get();
        $stat['ganti_menunggu'] = PengajuanJadwal::where('status', 'Menunggu')->count();

        return view('owner.dashboard', compact('stat', 'jadwalHariIni', 'absensi', 'grafik', 'keberatan'));
    }

    // Detail satu absensi: swafoto, catatan materi, dan peta lokasi
    public function show(Absensi $absensi)
    {
        $absensi->load(['jadwal.kursus.murid', 'jadwal.kursus.bidang', 'jadwal.kursus.mentor.user', 'riwayatCatatan']);

        return view('owner.absensi', [
            'absensi' => $absensi,
            'radius'     => AbsensiController::RADIUS,
            'radiusMaks' => AbsensiController::RADIUS_MAKS,
        ]);
    }
}
