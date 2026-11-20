<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Kursus;
use App\Models\LogAktivitas;
use App\Models\TanggapanVerifikasi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Pertemuan tambahan tanpa biaya (misalnya kompensasi atas keberatan wali).
// Diabsen mentor seperti biasa, tetapi tidak menambah honor dan tidak dihitung dalam paket.
class PertemuanTambahanController extends Controller
{
    public function store(Request $request, Kursus $kursus)
    {
        $kursus->load('pengajuanHonor', 'murid', 'bidang', 'mentor.user');
        if ($kursus->status !== 'diambil' || ! $kursus->id_mentor) {
            return back()->with('gagal', 'Pertemuan tambahan hanya dapat dibuat untuk kursus yang sudah memiliki mentor.');
        }
        if ($kursus->pengajuanTerakhir()) {
            return back()->with('gagal', 'Honor kursus ini sudah diajukan, sehingga pertemuan tambahan tidak dapat dibuat.');
        }

        $data = $request->validate([
            'id_kursus_tambahan' => 'required',
            'tanggal'     => 'required|date|after_or_equal:today',
            'jam_mulai'   => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'alasan'      => 'required|string|max:255',
        ], [
            'tanggal.required'       => 'Tanggal pertemuan tambahan wajib diisi.',
            'tanggal.after_or_equal' => 'Tanggal pertemuan tambahan tidak boleh sebelum hari ini.',
            'jam_mulai.required'     => 'Jam mulai wajib diisi.',
            'jam_selesai.required'   => 'Jam selesai wajib diisi.',
            'jam_selesai.after'      => 'Jam selesai harus setelah jam mulai.',
            'alasan.required'        => 'Alasan pertemuan tambahan wajib diisi.',
            'alasan.max'             => 'Alasan maksimal 255 karakter.',
        ]);

        $jadwal = $kursus->jadwal()->create([
            'pertemuan_ke' => $kursus->jadwal()->max('pertemuan_ke') + 1,
            'jenis'        => 'tambahan',
            'tanggal'      => $data['tanggal'],
            'jam_mulai'    => $data['jam_mulai'],
            'jam_selesai'  => $data['jam_selesai'],
            'keterangan'   => $data['alasan'],
        ]);

        $waktu = Carbon::parse($data['tanggal'])->translatedFormat('l, d F Y') . ' pukul ' . str_replace(':', '.', $data['jam_mulai']);
        LogAktivitas::catat('Menambah pertemuan tambahan',
            "{$kursus->bidang->nama_bidang} - {$kursus->murid->nama} (mentor {$kursus->mentor->user->nama}), pertemuan ke-{$jadwal->pertemuan_ke} pada {$waktu}. Alasan: {$data['alasan']}");

        // Diberitahukan di diskusi verifikasi jika wali sedang menyatakan belum sesuai
        if ($kursus->diskusiTerbuka()) {
            TanggapanVerifikasi::create([
                'id_kursus' => $kursus->id,
                'id_user'   => Auth::id(),
                'jenis'     => 'tanggapan',
                'isi'       => "Pertemuan tambahan tanpa biaya dijadwalkan pada {$waktu}. Alasan: {$data['alasan']}",
            ]);
        }

        return back()->with('sukses', "Pertemuan tambahan (ke-{$jadwal->pertemuan_ke}) dijadwalkan pada {$waktu}. Honor mentor tidak bertambah.");
    }

    // Menghapus pertemuan tambahan yang belum dilaksanakan
    public function destroy(Jadwal $jadwal)
    {
        $jadwal->load('kursus.murid', 'kursus.bidang', 'kursus.pengajuanHonor', 'absensi');
        if (! $jadwal->tambahan() || $jadwal->absensi || $jadwal->kursus->pengajuanTerakhir()) {
            return back()->with('gagal', 'Hanya pertemuan tambahan yang belum diabsen yang dapat dihapus.');
        }

        $k = $jadwal->kursus;
        $jadwal->delete();
        LogAktivitas::catat('Menghapus pertemuan tambahan',
            "{$k->bidang->nama_bidang} - {$k->murid->nama}, pertemuan ke-{$jadwal->pertemuan_ke} ({$jadwal->tanggal->translatedFormat('d M Y')})");

        return back()->with('sukses', 'Pertemuan tambahan dihapus.');
    }
}
