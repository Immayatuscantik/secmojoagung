<?php

namespace App\Http\Controllers;

use App\Models\Bidang;
use App\Models\KeahlianMentor;
use App\Models\LogAktivitas;
use App\Models\Murid;
use App\Models\User;
use App\Models\WaliMurid;
use App\Support\PendaftaranKursus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MuridController extends Controller
{
    // Daftar murid (owner/admin) dengan pencarian nama serta filter bidang, tingkat, dan status kursus
    public function index(Request $request)
    {
        [$murid, $cari, $bidang, $tingkat, $status] = $this->cariMurid($request);

        return view('owner.murid.index', [
            'murid'         => $murid,
            'cari'          => $cari,
            'bidang'        => $bidang,
            'tingkat'       => $tingkat,
            'status'        => $status,
            'daftarBidang'  => Bidang::orderBy('nama_bidang')->get(),
            'daftarTingkat' => KeahlianMentor::TINGKAT,
        ]);
    }

    // Cetak data murid: nama, alamat, kursus yang diambil, dan mentor (mengikuti filter cetak)
    public function cetak(Request $request)
    {
        $request->merge(['status' => null]);   // status kursus pada cetak dipilih lewat kotak centang
        [$murid, , $bidang] = $this->cariMurid($request);
        $pilihan = ['menunggu', 'tersedia', 'berjalan', 'selesai'];
        $status  = array_values(array_intersect((array) $request->input('status_kursus'), $pilihan)) ?: $pilihan;

        $murid->load(['kursus' => fn ($q) => $q
            ->when($bidang, fn ($x) => $x->where('id_bidang', $bidang))
            ->with(['bidang', 'mentor.user'])
            ->withCount(['jadwal as hadir_count' => fn ($j) => $j->where('jenis', 'rutin')->has('absensi')])]);

        $kunci = fn ($k) => match (true) {
            $k->status !== 'diambil' => $k->status,
            $k->pertemuanLengkap()   => 'selesai',
            default                  => 'berjalan',
        };

        $baris = $murid->map(fn ($m) => (object) [
                'murid'  => $m,
                'kursus' => $m->kursus->filter(fn ($k) => in_array($kunci($k), $status))->sortBy('bidang.nama_bidang')->values(),
            ])
            ->filter(fn ($b) => $request->boolean('tanpa_kursus', true) || $b->kursus->isNotEmpty())
            ->values();

        return view('owner.murid.cetak', ['baris' => $baris]);
    }

    // Query daftar murid yang dipakai halaman Data Murid dan cetak
    private function cariMurid(Request $request): array
    {
        $cari    = $request->cari;
        $bidang  = $request->integer('bidang') ?: null;
        $tingkat = in_array($request->tingkat, KeahlianMentor::TINGKAT) ? $request->tingkat : null;
        $status  = in_array($request->status, ['menunggu', 'tersedia', 'diambil']) ? $request->status : null;

        $murid = Murid::with(['wali.user', 'kursus.bidang'])
            ->when($cari, fn ($q) => $q->where('nama', 'like', "%{$cari}%"))
            // Tingkat diambil dari kata pertama jenjang, misalnya "SD kelas 5" -> SD
            ->when($tingkat, fn ($q) => $q->where(fn ($w) => $w
                ->where('jenjang', $tingkat)
                ->orWhere('jenjang', 'like', "{$tingkat} %")))
            // Bidang dan status harus cocok pada kursus yang sama
            ->when($bidang || $status, fn ($q) => $q->whereHas('kursus', fn ($k) => $k
                ->when($bidang, fn ($x) => $x->where('id_bidang', $bidang))
                ->when($status, fn ($x) => $x->where('status', $status))))
            ->orderBy('nama')
            ->get();

        return [$murid, $cari, $bidang, $tingkat, $status];
    }

    // Form tambah murid oleh owner/admin (untuk wali yang meminta didaftarkan oleh admin)
    public function create()
    {
        return view('owner.murid.create', [
            'murid'   => null,
            'jenjang' => Murid::JENJANG,
            'wali'    => WaliMurid::with('user')->get()->sortBy('user.nama'),
            'bidang'  => PendaftaranKursus::pilihanBidang(),
            'hari'    => PendaftaranKursus::HARI,
        ]);
    }

    // Menyimpan wali (lama/baru), data murid, dan kursus pertama (opsional) sekaligus
    public function store(Request $request)
    {
        $data = $request->validate(array_merge([
            'jenis_wali'     => 'required|in:lama,baru',
            'id_wali'        => 'required_if:jenis_wali,lama|nullable|exists:wali_murid,id',
            'wali_nama'      => 'required_if:jenis_wali,baru|nullable|string|max:50',
            'wali_hubungan'  => 'required_if:jenis_wali,baru|nullable|in:Ayah,Ibu,Wali',
            'wali_no_hp'     => 'required_if:jenis_wali,baru|nullable|digits_between:10,15',
            'wali_email'     => 'required_if:jenis_wali,baru|nullable|email|max:50|unique:users,email',
            'wali_pekerjaan' => 'nullable|string|max:50',
        ], Murid::aturanProfil(), PendaftaranKursus::aturan(false)), array_merge([
            'jenis_wali.required'        => 'Pilih wali yang sudah terdaftar atau buat wali baru.',
            'id_wali.required_if'        => 'Pilih wali murid.',
            'wali_nama.required_if'      => 'Nama wali wajib diisi.',
            'wali_hubungan.required_if'  => 'Hubungan wali dengan murid wajib dipilih.',
            'wali_no_hp.required_if'     => 'Nomor HP wali wajib diisi.',
            'wali_no_hp.digits_between'  => 'Nomor HP wali harus berupa angka 10–15 digit.',
            'wali_email.required_if'     => 'Email wali wajib diisi (dipakai untuk login wali).',
            'wali_email.email'           => 'Format email wali tidak valid.',
            'wali_email.unique'          => 'Email wali sudah terdaftar. Pilih "Wali sudah terdaftar".',
        ], Murid::pesanProfil(), PendaftaranKursus::pesan()), Murid::namaKolomProfil());

        $hasil = DB::transaction(function () use ($data) {
            // 1. Wali: pilih yang sudah ada, atau buatkan akun baru (kata sandi awal = nomor HP)
            if ($data['jenis_wali'] === 'lama') {
                $wali = WaliMurid::with('user')->find($data['id_wali']);
                $akunBaru = false;
            } else {
                $user = User::create([
                    'nama'     => $data['wali_nama'],
                    'email'    => $data['wali_email'],
                    'password' => $data['wali_no_hp'],
                    'no_hp'    => $data['wali_no_hp'],
                    'peran'    => 'wali',
                ]);
                $wali = $user->waliMurid()->create([
                    'hubungan'  => $data['wali_hubungan'],
                    'pekerjaan' => $data['wali_pekerjaan'] ?? null,
                    'alamat'    => $data['alamat'], // wali diasumsikan tinggal serumah dengan murid
                ]);
                $akunBaru = true;
            }

            // 2. Data pribadi murid
            $murid = $wali->murid()->create(collect($data)->only(array_keys(Murid::aturanProfil()))->all());

            // 3. Kursus pertama (opsional); kursus dari admin langsung berstatus "tersedia"
            $kursus = null;
            if (! empty($data['id_bidang'])) {
                $simpan = PendaftaranKursus::simpan($murid, $data, 'tersedia');
                if (isset($simpan['galat'])) {
                    throw ValidationException::withMessages(['hari' => $simpan['galat']]); // seluruh data dibatalkan
                }
                $kursus = $simpan['kursus'];
            }

            return compact('wali', 'murid', 'kursus', 'akunBaru');
        });

        ['wali' => $wali, 'murid' => $murid, 'kursus' => $kursus, 'akunBaru' => $akunBaru] = $hasil;

        LogAktivitas::catat('Menambah murid', "Murid: {$murid->nama}, wali: {$wali->user->nama}"
            . ($akunBaru ? ' (akun wali baru)' : ''));
        if ($kursus) {
            LogAktivitas::catat('Menambah kursus', "{$kursus->bidang->nama_bidang} - {$murid->nama} ({$kursus->jumlah_pertemuan} pertemuan)");
        }

        $pesan = "Data murid {$murid->nama} berhasil ditambahkan.";
        if ($akunBaru) {
            $pesan .= " Akun wali dibuat: email {$wali->user->email}, kata sandi awal = nomor HP ({$wali->user->no_hp}). Sampaikan kepada wali.";
        }

        return redirect()->route('owner.murid.show', [$murid, 'tab' => 'kursus'])->with('sukses', $pesan);
    }

    // Detail murid dengan tab: Profil Pribadi dan Kursus & Jadwal
    public function show(Request $request, Murid $murid)
    {
        $murid->load([
            'wali.user',
            'kursus' => fn ($q) => $q->latest(),
            'kursus.bidang',
            'kursus.mentor.user',
            'kursus.jadwal' => fn ($q) => $q->orderBy('pertemuan_ke'),
            'kursus.jadwal.absensi',
            'kursus.verifikator',
            'kursus.tanggapan.user',
            'kursus.jadwal.pengajuanJadwal.mentor.user',
        ]);

        $tab = $request->tab === 'kursus' ? 'kursus' : 'profil';

        return view('owner.murid.show', compact('murid', 'tab'));
    }

    // Ubah profil pribadi murid (misalnya memperbaiki kesalahan input wali)
    public function edit(Murid $murid)
    {
        return view('owner.murid.form', ['murid' => $murid, 'jenjang' => Murid::JENJANG]);
    }

    public function update(Request $request, Murid $murid)
    {
        $data = $request->validate(Murid::aturanProfil(), Murid::pesanProfil(), Murid::namaKolomProfil());

        $murid->update($data);
        LogAktivitas::catat('Mengubah data murid', 'Murid: ' . $murid->nama);

        return redirect()->route('owner.murid.show', $murid)->with('sukses', 'Data murid berhasil diperbarui.');
    }

    public function destroy(Murid $murid)
    {
        if ($murid->kursus()->where('status', 'diambil')->exists()) {
            return back()->with('gagal', 'Murid tidak dapat dihapus karena memiliki kursus yang sudah diambil mentor.');
        }

        $nama = $murid->nama;
        $murid->delete(); // kursus dan jadwal yang belum berjalan ikut terhapus
        LogAktivitas::catat('Menghapus data murid', 'Murid: ' . $nama);

        return redirect()->route('owner.murid.index')->with('sukses', 'Data murid berhasil dihapus.');
    }
}
