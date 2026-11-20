<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\PengajuanJadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Mentor mengajukan ganti hari; jadwal baru berlaku setelah disetujui owner/admin
class PengajuanJadwalController extends Controller
{
    public function store(Request $request, Jadwal $jadwal)
    {
        $mentor = Auth::user()->mentor;
        abort_unless((int) $jadwal->kursus->id_mentor === (int) $mentor->id, 404);

        $jadwal->load('kursus.pengajuanHonor', 'absensi');
        if (! $jadwal->bisaDiubah()) {
            return back()->with('gagal', 'Pertemuan ini sudah diabsen atau honornya sudah diajukan, sehingga tidak dapat diganti.');
        }
        if ($jadwal->pengajuanMenunggu()) {
            return back()->with('gagal', 'Pertemuan ini masih memiliki pengajuan ganti hari yang menunggu persetujuan admin.');
        }

        $data = $request->validate(JadwalController::aturan(), JadwalController::pesan());

        PengajuanJadwal::create([
            'id_jadwal'    => $jadwal->id,
            'id_mentor'    => $mentor->id,
            'tanggal_baru' => $data['tanggal'],
            'jam_mulai'    => $data['jam_mulai'],
            'jam_selesai'  => $data['jam_selesai'],
            'alasan'       => $data['alasan'],
        ]);

        return back()->with('sukses', "Pengajuan ganti hari untuk pertemuan ke-{$jadwal->pertemuan_ke} terkirim dan menunggu persetujuan admin.");
    }

    // Mentor menarik kembali pengajuan yang belum diproses
    public function destroy(PengajuanJadwal $pengajuan)
    {
        abort_unless((int) $pengajuan->id_mentor === (int) Auth::user()->mentor->id, 404);

        if ($pengajuan->status !== 'Menunggu') {
            return back()->with('gagal', 'Pengajuan yang sudah diproses tidak dapat ditarik.');
        }
        $pengajuan->delete();

        return back()->with('sukses', 'Pengajuan ganti hari ditarik kembali.');
    }
}
