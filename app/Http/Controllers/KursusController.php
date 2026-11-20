<?php

namespace App\Http\Controllers;

use App\Models\Kursus;
use App\Models\LogAktivitas;
use App\Models\Murid;
use App\Models\TanggapanVerifikasi;
use App\Support\PendaftaranKursus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KursusController extends Controller
{
    // Form tambah kursus untuk murid yang sudah terdaftar (oleh owner/admin)
    public function create(Murid $murid)
    {
        return view('owner.murid.kursus-form', [
            'murid'  => $murid,
            'bidang' => PendaftaranKursus::pilihanBidang($murid),
            'hari'   => PendaftaranKursus::HARI,
        ]);
    }

    // Kursus dari owner/admin langsung berstatus "tersedia" (tidak perlu konfirmasi lagi)
    public function store(Request $request, Murid $murid)
    {
        $data = $request->validate(PendaftaranKursus::aturan(), PendaftaranKursus::pesan());

        $hasil = PendaftaranKursus::simpan($murid, $data, 'tersedia');
        if (isset($hasil['galat'])) {
            return back()->withInput()->withErrors(['hari' => $hasil['galat']]);
        }
        LogAktivitas::catat('Menambah kursus', $this->uraian($hasil['kursus']));

        return redirect()->route('owner.murid.show', [$murid, 'tab' => 'kursus'])
            ->with('sukses', "Kursus {$hasil['kursus']->bidang->nama_bidang} berhasil ditambahkan dan langsung tampil di Daftar Job mentor.");
    }

    // Verifikasi pertemuan oleh admin/owner atas nama wali (misalnya setelah konfirmasi lewat WhatsApp)
    public function verifikasi(Request $request, Kursus $kursus)
    {
        if ($kursus->status !== 'diambil' || ! $kursus->pertemuanLengkap()) {
            return back()->with('gagal', 'Verifikasi hanya dapat dilakukan setelah seluruh pertemuan terpenuhi.');
        }
        if ($kursus->verifikasi_wali === 'disetujui') {
            return back()->with('gagal', 'Kursus ini sudah diverifikasi sebelumnya.');
        }

        $data = $request->validate([
            'keterangan_verifikasi' => 'required|string|max:255',
        ], [
            'keterangan_verifikasi.required' => 'Keterangan konfirmasi wali wajib diisi.',
        ]);

        $kursus->update([
            'verifikasi_wali'    => 'disetujui',
            'alasan_wali'        => null,
            'tanggal_verifikasi' => now(),
            'id_verifikator'     => Auth::id(),
        ]);
        TanggapanVerifikasi::create([
            'id_kursus' => $kursus->id,
            'id_user'   => Auth::id(),
            'jenis'     => 'persetujuan',
            'isi'       => 'Diverifikasi atas nama wali. ' . $data['keterangan_verifikasi'],
        ]);
        LogAktivitas::catat('Memverifikasi pertemuan atas nama wali',
            $this->uraian($kursus) . '. Keterangan: ' . $data['keterangan_verifikasi']);

        return back()->with('sukses', 'Pertemuan diverifikasi atas nama wali. Mentor sekarang dapat mengajukan honor.');
    }

    // Konfirmasi kursus yang didaftarkan wali -> tampil di Daftar Job mentor
    public function konfirmasi(Kursus $kursus)
    {
        if ($kursus->status !== 'menunggu') {
            return back()->with('gagal', 'Kursus ini sudah dikonfirmasi sebelumnya.');
        }

        $kursus->update(['status' => 'tersedia']);
        LogAktivitas::catat('Mengonfirmasi kursus', $this->uraian($kursus));

        return back()->with('sukses', "Kursus {$kursus->bidang->nama_bidang} untuk {$kursus->murid->nama} dikonfirmasi dan sekarang tampil di Daftar Job mentor.");
    }

    // Menghapus kursus yang belum berjalan (belum ada absensi)
    public function destroy(Kursus $kursus)
    {
        if ($kursus->jadwal()->has('absensi')->exists() || $kursus->pengajuanHonor()->exists()) {
            return back()->with('gagal', 'Kursus tidak dapat dihapus karena sudah memiliki absensi.');
        }

        $uraian = $this->uraian($kursus);
        $kursus->delete(); // jadwal ikut terhapus
        LogAktivitas::catat('Menghapus kursus', $uraian);

        return back()->with('sukses', 'Kursus berhasil dihapus.');
    }

    private function uraian(Kursus $k): string
    {
        return "{$k->bidang->nama_bidang} - {$k->murid->nama} ({$k->jumlah_pertemuan} pertemuan)";
    }
}
