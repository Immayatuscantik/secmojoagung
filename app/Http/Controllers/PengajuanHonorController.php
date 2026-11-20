<?php

namespace App\Http\Controllers;

use App\Models\Kursus;
use App\Models\PengajuanHonor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PengajuanHonorController extends Controller
{
    // Halaman Status Honor
    public function index()
    {
        $pengajuan = PengajuanHonor::where('id_mentor', $this->idMentor())
            ->with(['kursus.murid', 'kursus.bidang', 'pencairan'])
            ->latest('tanggal_pengajuan')
            ->latest('id')
            ->get();

        return view('mentor.honor.index', compact('pengajuan'));
    }

    // Form pengajuan honor untuk satu kursus
    public function create(Kursus $kursus)
    {
        $this->pastikanMilikMentor($kursus);

        if ($pesan = self::cekBolehAjukan($kursus)) {
            return redirect()->route('mentor.murid.show', $kursus)->with('gagal', $pesan);
        }

        $kursus->load(['murid', 'bidang', 'jadwal' => fn ($q) => $q->orderBy('pertemuan_ke'), 'jadwal.absensi']);

        return view('mentor.honor.form', [
            'kursus'    => $kursus,
            'pengajuan' => $kursus->pengajuanTerakhir(),
        ]);
    }

    // Menyimpan pengajuan honor (baru atau pengajuan ulang)
    public function store(Request $request, Kursus $kursus)
    {
        $this->pastikanMilikMentor($kursus);

        if ($pesan = self::cekBolehAjukan($kursus)) {
            return redirect()->route('mentor.murid.show', $kursus)->with('gagal', $pesan);
        }

        // R9: link video pembelajaran (Google Drive) wajib diisi
        $data = $request->validate([
            'video' => ['required', 'url', 'max:100', 'regex:/^https:\/\/drive\.google\.com\//'],
        ], [
            'video.required' => 'Link video pembelajaran wajib diisi.',
            'video.url'      => 'Link video tidak valid.',
            'video.regex'    => 'Link video harus berasal dari Google Drive (https://drive.google.com/...).',
            'video.max'      => 'Link video maksimal 100 karakter.',
        ]);

        $pengajuan = $kursus->pengajuanTerakhir();

        if ($pengajuan) {
            // R10: pengajuan yang dikembalikan diajukan ulang dengan link baru
            $pengajuan->update([
                'tanggal_pengajuan'   => today(),
                'video'               => $data['video'],
                'alasan_pengembalian' => null,
                'status'              => 'Diajukan',
            ]);
            $pesan = 'Pengajuan honor berhasil diajukan ulang.';
        } else {
            PengajuanHonor::create([
                'id_mentor'         => $this->idMentor(),
                'id_kursus'         => $kursus->id,
                'tanggal_pengajuan' => today(),
                'video'             => $data['video'],
                'status'            => 'Diajukan',
            ]);
            $pesan = 'Pengajuan honor berhasil dikirim dan menunggu verifikasi owner.';
        }

        return redirect()->route('mentor.honor')->with('sukses', $pesan);
    }

    // R7, R8, R10 + verifikasi wali: menentukan apakah honor boleh diajukan
    public static function cekBolehAjukan(Kursus $kursus): ?string
    {
        $hadir = $kursus->jumlahHadir();
        if (! $kursus->pertemuanLengkap()) {
            return "Honor hanya dapat diajukan setelah seluruh pertemuan terpenuhi ({$hadir}/{$kursus->jumlah_pertemuan}).";
        }

        if ($sisa = $kursus->tambahanBelumSelesai()->first()) {
            return 'Pertemuan tambahan pada ' . $sisa->tanggal->translatedFormat('d F Y') . ' belum dilaksanakan. Honor dapat diajukan setelah pertemuan tambahan diabsen.';
        }

        if ($kursus->verifikasi_wali !== 'disetujui') {
            return 'Honor dapat diajukan setelah wali murid memverifikasi bahwa seluruh pertemuan telah sesuai.';
        }

        $pengajuan = $kursus->pengajuanTerakhir();
        if ($pengajuan && $pengajuan->status !== 'Dikembalikan') {
            return 'Honor untuk kursus ini sudah diajukan.';
        }

        return null;
    }

    private function idMentor(): int
    {
        return (int) Auth::user()->mentor->id;
    }

    private function pastikanMilikMentor(Kursus $kursus): void
    {
        abort_if((int) $kursus->id_mentor !== $this->idMentor(), 404);
    }
}
