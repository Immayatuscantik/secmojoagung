<?php

namespace App\Http\Controllers;

use App\Models\Bidang;
use App\Models\KeahlianMentor;
use App\Models\LogAktivitas;
use App\Models\Mentor;
use App\Models\User;
use App\Support\Portofolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MentorController extends Controller
{
    const PENDIDIKAN = ['SMA/SMK', 'D3', 'D4', 'S1', 'S2'];

    // Daftar mentor dengan pencarian nama/email serta filter bidang dan tingkat
    public function index(Request $request)
    {
        $cari    = $request->cari;
        $bidang  = $request->integer('bidang') ?: null;
        $tingkat = in_array($request->tingkat, KeahlianMentor::TINGKAT) ? $request->tingkat : null;

        $mentor = Mentor::with(['user', 'bidang', 'keahlian'])
            ->withCount(['kursus' => fn ($q) => $q->where('status', 'diambil')])
            ->when($cari, fn ($q) => $q->whereHas('user', fn ($u) => $u->where(fn ($w) => $w
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('email', 'like', "%{$cari}%"))))
            // Bidang dan tingkat harus cocok pada baris keahlian yang sama
            ->when($bidang || $tingkat, fn ($q) => $q->whereHas('keahlian', fn ($k) => $k
                ->when($bidang, fn ($x) => $x->where('id_bidang', $bidang))
                ->when($tingkat, fn ($x) => $x->where('tingkat', $tingkat))))
            ->get()
            ->sortBy('user.nama');

        return view('owner.mentor.index', [
            'mentor'       => $mentor,
            'cari'         => $cari,
            'bidang'       => $bidang,
            'tingkat'      => $tingkat,
            'daftarBidang' => Bidang::orderBy('nama_bidang')->get(),
            'daftarTingkat' => KeahlianMentor::TINGKAT,
        ]);
    }

    // Detail mentor dengan tab: Profil Pribadi, Bidang Dikuasai, Kursus yang Diajar
    public function show(Request $request, Mentor $mentor)
    {
        $mentor->load(['user', 'bidang', 'keahlian', 'kursus' => fn ($q) => $q
            ->with(['murid', 'bidang', 'jadwal'])
            ->withCount(['jadwal as hadir_count' => fn ($j) => $j->where('jenis', 'rutin')->has('absensi')])]);

        $tab = in_array($request->tab, ['profil', 'bidang', 'kursus', 'portofolio']) ? $request->tab : 'profil';

        // Tab Portofolio: rekam jejak mengajar dan honor mentor
        $portofolio = $tab === 'portofolio'
            ? Portofolio::data($mentor, $request) + [
                'rute'      => 'owner.mentor.show',
                'dasar'     => ['mentor' => $mentor->id, 'tab' => 'portofolio'],
                'pengelola' => true,
            ]
            : [];

        return view('owner.mentor.show', compact('mentor', 'tab') + $portofolio);
    }

    public function create()
    {
        return view('owner.mentor.form', [
            'mentor'     => null,
            'bidang'     => Bidang::with('tarifHonor')->orderBy('nama_bidang')->get(),
            'pendidikan' => self::PENDIDIKAN,
        ]);
    }

    public function store(Request $request)
    {
        $keahlian = $this->saringKeahlian($request);
        $data = $request->validate($this->aturan(), $this->pesan(), $this->namaKolom());

        // Akun (users), profil (mentor), dan keahlian (mentor_bidang) disimpan bersamaan
        $mentor = DB::transaction(function () use ($data, $keahlian) {
            $user = User::create([
                'nama'     => $data['nama'],
                'email'    => $data['email'],
                'password' => $data['password'],
                'no_hp'    => $data['no_hp'],
                'peran'    => 'mentor',
            ]);

            $mentor = $user->mentor()->create($this->dataProfil($data));
            $mentor->simpanKeahlian($keahlian);

            return $mentor;
        });

        LogAktivitas::catat('Menambah mentor', 'Mentor: ' . $mentor->user->nama);

        return redirect()->route('owner.mentor.show', $mentor)->with('sukses', 'Data mentor berhasil ditambahkan.');
    }

    public function edit(Mentor $mentor)
    {
        $mentor->load(['user', 'bidang', 'keahlian']);

        return view('owner.mentor.form', [
            'mentor'     => $mentor,
            'bidang'     => Bidang::with('tarifHonor')->orderBy('nama_bidang')->get(),
            'pendidikan' => self::PENDIDIKAN,
        ]);
    }

    public function update(Request $request, Mentor $mentor)
    {
        $keahlian = $this->saringKeahlian($request);
        $data = $request->validate($this->aturan($mentor), $this->pesan(), $this->namaKolom());

        DB::transaction(function () use ($data, $mentor, $keahlian) {
            $akun = [
                'nama'  => $data['nama'],
                'email' => $data['email'],
                'no_hp' => $data['no_hp'],
            ];
            if (! empty($data['password'])) {
                $akun['password'] = $data['password']; // kata sandi tidak diubah jika dikosongkan
            }

            $mentor->user->update($akun);
            $mentor->update($this->dataProfil($data));
            $mentor->simpanKeahlian($keahlian);
        });

        LogAktivitas::catat('Mengubah mentor', 'Mentor: ' . $mentor->user->nama);

        return redirect()->route('owner.mentor.show', $mentor)->with('sukses', 'Data mentor berhasil diperbarui.');
    }

    public function destroy(Mentor $mentor)
    {
        // Mentor yang sudah pernah mengambil kursus tidak dihapus agar riwayatnya tetap ada
        if ($mentor->kursus()->exists() || $mentor->pengajuanHonor()->exists()) {
            return back()->with('gagal', 'Mentor tidak dapat dihapus karena sudah memiliki riwayat mengajar.');
        }

        $nama = $mentor->user->nama;
        $mentor->user->delete(); // profil mentor dan bidangnya ikut terhapus (cascade)
        LogAktivitas::catat('Menghapus mentor', 'Mentor: ' . $nama);

        return redirect()->route('owner.mentor.index')->with('sukses', 'Data mentor berhasil dihapus.');
    }

    // Keahlian dikirim dari form sebagai keahlian[id_bidang][] = tingkat.
    // Tingkat yang boleh dipilih untuk setiap bidang mengikuti tarif honor bidang tersebut.
    // Hasilnya ditandai pada kolom bantu "keahlian_sah" agar ikut divalidasi bersama kolom lain.
    private function saringKeahlian(Request $request): array
    {
        $hasil = [];
        $bidang = Bidang::with('tarifHonor')->get()->keyBy('id');

        foreach ((array) $request->input('keahlian', []) as $idBidang => $daftarTingkat) {
            if (! isset($bidang[$idBidang]) || ! is_array($daftarTingkat)) {
                continue;
            }
            $sah = array_intersect($daftarTingkat, $bidang[$idBidang]->tingkatTersedia());
            if ($sah) {
                $hasil[(int) $idBidang] = array_values(array_unique($sah));
            }
        }

        $request->merge(['keahlian_sah' => $hasil ? 1 : null]);

        return $hasil;
    }

    private function dataProfil(array $data): array
    {
        return collect($data)->only([
            'jenis_kelamin', 'tanggal_lahir', 'alamat', 'latitude', 'longitude',
            'pendidikan_terakhir', 'asal_institusi', 'pengalaman_mengajar',
        ])->all();
    }

    private function aturan(?Mentor $mentor = null): array
    {
        return [
            // Akun
            'nama'     => 'required|string|max:50',
            'email'    => ['required', 'email', 'max:50', Rule::unique('users', 'email')->ignore($mentor?->id_user)],
            'no_hp'    => 'required|string|max:15',
            'password' => [$mentor ? 'nullable' : 'required', 'string', 'min:10', 'max:15'],
            // Profil pribadi
            'jenis_kelamin'       => 'required|in:Laki-laki,Perempuan',
            'tanggal_lahir'       => 'required|date|before:today',
            'pendidikan_terakhir' => ['required', Rule::in(self::PENDIDIKAN)],
            'asal_institusi'      => 'required|string|max:100',
            'pengalaman_mengajar' => 'nullable|string|max:1000',
            // Keahlian: minimal satu bidang + tingkat
            'keahlian_sah' => 'required',
            // Alamat rumah
            'alamat'    => 'required|string|max:255',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ];
    }

    private function pesan(): array
    {
        return [
            'required'             => 'Kolom :attribute wajib diisi.',
            'email.email'          => 'Format email tidak valid.',
            'email.unique'         => 'Email sudah digunakan oleh akun lain.',
            'password.min'         => 'Panjang kata sandi tidak sesuai (10–15 karakter).',
            'password.max'         => 'Panjang kata sandi tidak sesuai (10–15 karakter).',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'keahlian_sah.required' => 'Pilih minimal satu bidang beserta tingkat yang dikuasai.',
            'alamat.required'      => 'Alamat rumah mentor wajib diisi.',
            'latitude.required'    => 'Lokasi rumah mentor wajib dipilih pada peta.',
            'longitude.required'   => 'Lokasi rumah mentor wajib dipilih pada peta.',
        ];
    }

    private function namaKolom(): array
    {
        return [
            'nama' => 'nama', 'email' => 'email', 'no_hp' => 'nomor HP', 'password' => 'kata sandi',
            'jenis_kelamin' => 'jenis kelamin', 'tanggal_lahir' => 'tanggal lahir',
            'pendidikan_terakhir' => 'pendidikan terakhir', 'asal_institusi' => 'asal institusi',
        ];
    }
}
