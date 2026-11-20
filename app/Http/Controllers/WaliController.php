<?php

namespace App\Http\Controllers;

use App\Models\Kursus;
use App\Models\Murid;
use App\Models\TanggapanVerifikasi;
use App\Support\PendaftaranKursus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WaliController extends Controller
{
    // Anak Saya: daftar anak beserta ringkasan kursusnya
    public function index()
    {
        $anak = $this->wali()->murid()
            ->with([
                'kursus' => fn ($q) => $q->latest()->withCount(['jadwal as hadir_count' => fn ($j) => $j->where('jenis', 'rutin')->has('absensi')]),
                'kursus.bidang',
                'kursus.mentor.user',
            ])
            ->orderBy('nama')
            ->get();

        return view('wali.anak', compact('anak'));
    }

    // ===== Profil pribadi anak =====

    public function create()
    {
        return view('wali.anak-form', ['murid' => null, 'jenjang' => Murid::JENJANG]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(Murid::aturanProfil(), Murid::pesanProfil(), Murid::namaKolomProfil());

        $murid = $this->wali()->murid()->create($data);

        return redirect()->route('wali.kursus.create', $murid)
            ->with('sukses', "Data {$murid->nama} tersimpan. Silakan pilih kursus yang ingin diikuti.");
    }

    public function show(Request $request, Murid $murid)
    {
        $this->pastikanAnakSaya($murid);

        $murid->load([
            'kursus' => fn ($q) => $q->latest(),
            'kursus.bidang',
            'kursus.mentor.user',
            'kursus.jadwal' => fn ($q) => $q->orderBy('pertemuan_ke'),
            'kursus.jadwal.absensi',
            'kursus.verifikator',
            'kursus.tanggapan.user',
        ]);

        $tab = $request->tab === 'kursus' ? 'kursus' : 'profil';

        return view('wali.anak-show', compact('murid', 'tab'));
    }

    public function edit(Murid $murid)
    {
        $this->pastikanAnakSaya($murid);

        return view('wali.anak-form', ['murid' => $murid, 'jenjang' => Murid::JENJANG]);
    }

    public function update(Request $request, Murid $murid)
    {
        $this->pastikanAnakSaya($murid);

        $data = $request->validate(Murid::aturanProfil(), Murid::pesanProfil(), Murid::namaKolomProfil());
        $murid->update($data);

        return redirect()->route('wali.anak.show', $murid)->with('sukses', 'Profil anak berhasil diperbarui.');
    }

    // ===== Kursus dan jadwal =====

    public function createKursus(Murid $murid)
    {
        $this->pastikanAnakSaya($murid);

        return view('wali.kursus-form', [
            'murid'  => $murid,
            // Hanya bidang yang memiliki tarif untuk jenjang anak yang bisa dipilih
            'bidang' => PendaftaranKursus::pilihanBidang($murid),
            'hari'   => PendaftaranKursus::HARI,
        ]);
    }

    public function storeKursus(Request $request, Murid $murid)
    {
        $this->pastikanAnakSaya($murid);

        $data = $request->validate(PendaftaranKursus::aturan(), PendaftaranKursus::pesan());

        // Kursus dari wali menunggu konfirmasi owner/admin
        $hasil = PendaftaranKursus::simpan($murid, $data, 'menunggu');
        if (isset($hasil['galat'])) {
            return back()->withInput()->withErrors(['hari' => $hasil['galat']]);
        }
        $kursus = $hasil['kursus'];

        return redirect()->route('wali.anak.show', [$murid, 'tab' => 'kursus'])
            ->with('sukses', "Pendaftaran kursus {$kursus->bidang->nama_bidang} ({$kursus->jumlah_pertemuan} pertemuan) berhasil dan menunggu konfirmasi admin.");
    }

    // Wali membatalkan kursus yang belum dikonfirmasi
    public function batalKursus(Kursus $kursus)
    {
        $this->pastikanAnakSaya($kursus->murid);

        if ($kursus->status !== 'menunggu') {
            return back()->with('gagal', 'Kursus yang sudah dikonfirmasi tidak dapat dibatalkan. Silakan hubungi admin.');
        }

        $kursus->delete();

        return back()->with('sukses', 'Pendaftaran kursus berhasil dibatalkan.');
    }

    // Verifikasi wali setelah seluruh pertemuan terpenuhi (syarat pengajuan honor mentor)
    public function verifikasi(Request $request, Kursus $kursus)
    {
        $this->pastikanAnakSaya($kursus->murid);

        if ($kursus->status !== 'diambil' || ! $kursus->pertemuanLengkap()) {
            return back()->with('gagal', 'Verifikasi hanya dapat dilakukan setelah seluruh pertemuan terpenuhi.');
        }
        if ($kursus->verifikasi_wali === 'disetujui') {
            return back()->with('gagal', 'Kursus ini sudah Anda setujui sebelumnya.');
        }

        $data = $request->validate([
            'keputusan'   => 'required|in:disetujui,belum_sesuai',
            'alasan_wali' => 'nullable|required_if:keputusan,belum_sesuai|string|max:500',
        ], [
            'keputusan.required'      => 'Pilih keputusan verifikasi.',
            'alasan_wali.required_if' => 'Alasan wajib diisi jika pertemuan belum sesuai.',
            'alasan_wali.max'         => 'Alasan maksimal 500 karakter.',
        ]);

        $setuju = $data['keputusan'] === 'disetujui';
        $kursus->update([
            'verifikasi_wali'    => $data['keputusan'],
            'alasan_wali'        => $setuju ? null : $data['alasan_wali'],
            'tanggal_verifikasi' => now(),
            'id_verifikator'     => Auth::id(),
        ]);

        // Dicatat di diskusi agar mentor dan owner/admin dapat mengikuti prosesnya
        TanggapanVerifikasi::create([
            'id_kursus' => $kursus->id,
            'id_user'   => Auth::id(),
            'jenis'     => $setuju ? 'persetujuan' : 'keberatan',
            'isi'       => $setuju ? (($data['alasan_wali'] ?? null) ?: 'Wali menyetujui seluruh pertemuan.') : $data['alasan_wali'],
        ]);

        return back()->with('sukses', $setuju
            ? 'Terima kasih. Kursus telah Anda setujui dan mentor dapat mengajukan honor.'
            : 'Keberatan Anda telah dikirim kepada mentor dan admin. Tanggapan mentor akan tampil di bagian Diskusi verifikasi.');
    }

    // ===== Bantuan =====

    private function wali()
    {
        return Auth::user()->waliMurid;
    }

    // Wali hanya boleh membuka data anaknya sendiri
    private function pastikanAnakSaya(Murid $murid): void
    {
        abort_unless((int) $murid->id_wali === (int) $this->wali()->id, 404);
    }
}
