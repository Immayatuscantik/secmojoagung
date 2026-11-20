<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\LogAktivitas;
use App\Models\PengajuanJadwal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Jadwal pengganti oleh owner/admin: langsung mengganti, atau memproses pengajuan ganti hari dari mentor
class JadwalController extends Controller
{
    // Daftar pengajuan ganti hari dari mentor
    public function index(Request $request)
    {
        $request->validate(['status' => 'nullable|in:Menunggu,Disetujui,Ditolak']);
        $status = $request->status ?? 'Menunggu';

        $pengajuan = PengajuanJadwal::where('status', $status)
            ->with(['jadwal.kursus.murid.wali.user', 'jadwal.kursus.bidang', 'jadwal.absensi', 'jadwal.kursus.pengajuanHonor', 'mentor.user', 'pemroses'])
            ->orderBy($status === 'Menunggu' ? 'created_at' : 'diproses_pada', $status === 'Menunggu' ? 'asc' : 'desc')
            ->paginate(15)
            ->withQueryString();

        $jumlah = PengajuanJadwal::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('owner.jadwal.index', compact('pengajuan', 'status', 'jumlah'));
    }

    // Owner/admin langsung memindahkan pertemuan ke tanggal lain
    public function ganti(Request $request, Jadwal $jadwal)
    {
        $this->pastikanBisaDiubah($jadwal);

        $data = $request->validate(self::aturan(), self::pesan());
        $data['keterangan'] = $data['alasan'];

        $baru = $this->terapkan($jadwal, $data);
        LogAktivitas::catat('Mengganti jadwal pertemuan', $this->uraian($jadwal) . " ke {$baru}. Alasan: {$data['alasan']}");

        // Pengajuan mentor yang masih menunggu untuk pertemuan ini dianggap selesai
        $jadwal->pengajuanJadwal()->where('status', 'Menunggu')->update([
            'status' => 'Ditolak', 'catatan_pengelola' => 'Jadwal sudah diganti langsung oleh admin ke ' . $baru . '.',
            'id_pemroses' => Auth::id(), 'diproses_pada' => now(),
        ]);

        return back()->with('sukses', "Pertemuan ke-{$jadwal->pertemuan_ke} dipindah ke {$baru}.");
    }

    // Menyetujui pengajuan ganti hari dari mentor -> jadwal langsung berubah
    public function setujui(PengajuanJadwal $pengajuan)
    {
        if ($pengajuan->status !== 'Menunggu') {
            return back()->with('gagal', 'Pengajuan ini sudah diproses sebelumnya.');
        }
        $jadwal = $pengajuan->jadwal;
        $jadwal->load('kursus.pengajuanHonor', 'absensi');
        if (! $jadwal->bisaDiubah()) {
            return back()->with('gagal', 'Pertemuan ini sudah diabsen atau honornya sudah diajukan, sehingga jadwal tidak dapat diganti. Tolak pengajuan ini.');
        }
        if ($pengajuan->tanggal_baru->lt(today())) {
            return back()->with('gagal', 'Tanggal yang diajukan sudah lewat. Tolak pengajuan ini atau ganti jadwal secara langsung.');
        }

        $baru = $this->terapkan($jadwal, [
            'tanggal'     => $pengajuan->tanggal_baru->toDateString(),
            'jam_mulai'   => substr($pengajuan->jam_mulai, 0, 5),
            'jam_selesai' => substr($pengajuan->jam_selesai, 0, 5),
            'keterangan'  => $pengajuan->alasan . ' (diajukan mentor)',
        ]);
        $pengajuan->update(['status' => 'Disetujui', 'id_pemroses' => Auth::id(), 'diproses_pada' => now()]);
        LogAktivitas::catat('Menyetujui pengajuan ganti jadwal', $this->uraian($jadwal) . " ke {$baru}. Alasan mentor: {$pengajuan->alasan}");

        return back()->with('sukses', "Pengajuan disetujui. Pertemuan ke-{$jadwal->pertemuan_ke} dipindah ke {$baru}.");
    }

    // Menolak pengajuan ganti hari dari mentor (alasan wajib)
    public function tolak(Request $request, PengajuanJadwal $pengajuan)
    {
        if ($pengajuan->status !== 'Menunggu') {
            return back()->with('gagal', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $data = $request->validate([
            'id_pengajuan_tolak' => 'required',
            'catatan_pengelola'  => 'required|string|max:255',
        ], [
            'catatan_pengelola.required' => 'Alasan penolakan wajib diisi.',
            'catatan_pengelola.max'      => 'Alasan penolakan maksimal 255 karakter.',
        ]);

        $pengajuan->update([
            'status' => 'Ditolak', 'catatan_pengelola' => $data['catatan_pengelola'],
            'id_pemroses' => Auth::id(), 'diproses_pada' => now(),
        ]);
        LogAktivitas::catat('Menolak pengajuan ganti jadwal', $this->uraian($pengajuan->jadwal) . ". Alasan: {$data['catatan_pengelola']}");

        return back()->with('sukses', 'Pengajuan ganti jadwal ditolak dan alasannya dapat dilihat mentor.');
    }

    // ===== Dipakai bersama (juga oleh PengajuanJadwalController) =====

    public static function aturan(): array
    {
        return [
            'id_jadwal_ubah' => 'required',
            'tanggal'     => 'required|date|after_or_equal:today',
            'jam_mulai'   => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'alasan'      => 'required|string|max:255',
        ];
    }

    public static function pesan(): array
    {
        return [
            'tanggal.required'       => 'Tanggal pengganti wajib diisi.',
            'tanggal.after_or_equal' => 'Tanggal pengganti tidak boleh sebelum hari ini.',
            'jam_mulai.required'     => 'Jam mulai wajib diisi.',
            'jam_selesai.required'   => 'Jam selesai wajib diisi.',
            'jam_selesai.after'      => 'Jam selesai harus setelah jam mulai.',
            'alasan.required'        => 'Alasan penggantian wajib diisi.',
            'alasan.max'             => 'Alasan maksimal 255 karakter.',
        ];
    }

    // Memindahkan jadwal; tanggal semula tetap tercatat di tanggal_asal
    private function terapkan(Jadwal $jadwal, array $data): string
    {
        $jadwal->update([
            'tanggal_asal' => $jadwal->tanggal_asal ?? $jadwal->tanggal,
            'tanggal'      => $data['tanggal'],
            'jam_mulai'    => $data['jam_mulai'],
            'jam_selesai'  => $data['jam_selesai'],
            'keterangan'   => $data['keterangan'],
        ]);

        return Carbon::parse($data['tanggal'])->translatedFormat('d M Y') . ' ' . str_replace(':', '.', $data['jam_mulai']);
    }

    private function pastikanBisaDiubah(Jadwal $jadwal): void
    {
        $jadwal->load('kursus.pengajuanHonor', 'absensi');
        abort_unless($jadwal->bisaDiubah(), 403, 'Jadwal ini sudah diabsen atau honornya sudah diajukan.');
    }

    private function uraian(Jadwal $jadwal): string
    {
        $k = $jadwal->kursus->load('murid', 'bidang', 'mentor.user');

        return "Pertemuan ke-{$jadwal->pertemuan_ke} {$k->bidang->nama_bidang} - {$k->murid->nama} (mentor {$k->mentor?->user->nama})";
    }
}
