<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\Bidang;
use App\Models\Kursus;
use App\Models\LogAktivitas;
use App\Models\Mentor;
use App\Models\Murid;
use App\Models\PengajuanHonor;
use App\Models\PengajuanJadwal;
use App\Models\RiwayatCatatan;
use App\Models\TanggapanVerifikasi;
use App\Models\TarifHonor;
use App\Models\User;
use App\Models\WaliMurid;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Data dummy untuk pengujian dan demo: riwayat kursus, absensi, verifikasi wali,
 * pengajuan dan pencairan honor selama kurang lebih 6 bulan terakhir.
 *
 * Jalankan (MENGHAPUS SEMUA DATA LAMA):
 *   php artisan migrate:fresh --seeder=DataDummySeeder
 */
class DataDummySeeder extends Seeder
{
    private Carbon $hariIni;
    private array $bidang = [];
    private User $admin;
    private User $owner;
    private int $nomorFoto = 0;

    // Contoh catatan materi per bidang (dipakai berurutan setiap pertemuan)
    const MATERI = [
        'Mapel Umum' => [
            'Matematika: penjumlahan dan pengurangan bersusun, latihan 10 soal.',
            'Bahasa Indonesia: membaca pemahaman dan menjawab pertanyaan bacaan.',
            'Matematika: perkalian dan pembagian, hafalan perkalian 6-9.',
            'IPA: bagian tubuh tumbuhan dan fungsinya, mengerjakan LKS.',
            'Matematika: pecahan senilai dan menyederhanakan pecahan.',
            'PR sekolah: tematik, mendampingi mengerjakan tugas halaman 45-48.',
            'Matematika: soal cerita KPK dan FPB.',
            'Persiapan ulangan harian: latihan soal campuran.',
            'Bahasa Inggris sekolah: kosakata benda di kelas dan latihan kalimat.',
            'IPS: kenampakan alam dan buatan, membuat ringkasan.',
            'Matematika: bangun datar, keliling dan luas.',
            'Evaluasi: mengerjakan soal latihan dan membahas jawaban yang salah.',
        ],
        'Mapel SMA' => [
            'Matematika: fungsi kuadrat dan grafiknya.',
            'Fisika: gerak lurus berubah beraturan, latihan soal.',
            'Kimia: konfigurasi elektron dan tabel periodik.',
            'Matematika: trigonometri dasar, sudut istimewa.',
            'Fisika: hukum Newton dan penerapannya.',
            'Persiapan PTS: pembahasan soal tahun lalu.',
            'Matematika: barisan dan deret aritmetika.',
            'Kimia: stoikiometri sederhana.',
        ],
        'Sempoa' => [
            'Pengenalan manik sempoa dan posisi jari.',
            'Penjumlahan langsung 1 digit dengan sempoa.',
            'Penjumlahan teman kecil (+5).',
            'Pengurangan teman kecil (-5).',
            'Penjumlahan teman besar (+10).',
            'Latihan mental aritmetika (membayangkan sempoa).',
            'Penjumlahan dan pengurangan 2 digit, latihan kecepatan.',
            'Evaluasi: 50 soal dalam 5 menit.',
        ],
        'Bahasa Inggris' => [
            'Vocabulary: family members, latihan menyebutkan anggota keluarga.',
            'Grammar: simple present tense (positive & negative).',
            'Speaking: introducing yourself.',
            'Reading: short story dan menjawab pertanyaan.',
            'Vocabulary: daily activities, membuat kalimat.',
            'Grammar: simple past tense, regular verbs.',
            'Listening: lagu anak berbahasa Inggris dan melengkapi lirik.',
            'Review dan kuis kosakata.',
        ],
        'Komputer' => [
            'Pengenalan perangkat komputer dan cara menyalakan/mematikan.',
            'Microsoft Word: mengetik dan memformat teks.',
            'Microsoft Word: menyisipkan tabel dan gambar.',
            'Microsoft Excel: mengisi data dan rumus SUM.',
            'Microsoft Excel: rumus AVERAGE, MAX, MIN.',
            'Microsoft PowerPoint: membuat slide presentasi sederhana.',
            'Internet sehat: mencari informasi dan email.',
            'Proyek: membuat laporan tugas sekolah dengan Word.',
        ],
    ];

    public function run(): void
    {
        mt_srand(2026); // hasil acak selalu sama setiap kali dijalankan

        // 1. Data dasar: bidang, tarif, akun owner/admin/mentor/wali contoh, dan job hari ini
        $this->call(DatabaseSeeder::class);

        $this->hariIni = today();
        $this->bidang  = Bidang::all()->keyBy('nama_bidang')->all();
        $this->owner   = User::where('peran', 'owner')->first();
        $this->admin   = User::where('peran', 'admin')->first();
        $awalData      = $this->minggu(24);

        // Akun dan data contoh dari DatabaseSeeder dianggap sudah ada sejak awal
        User::query()->update(['created_at' => $awalData, 'updated_at' => $awalData]);
        foreach (['mentor', 'wali_murid', 'murid'] as $tabel) {
            DB::table($tabel)->update(['created_at' => $awalData, 'updated_at' => $awalData]);
        }

        $this->siapkanGambar();

        // 2. Mentor
        $m1 = User::where('email', 'mentor@sec.test')->first()->mentor;
        $m2 = User::where('email', 'mentor2@sec.test')->first()->mentor;
        $m3 = $this->mentor('Rina Kartika', 'rina@sec.test', 'Perempuan', '2000-02-17', 'Desa Mojotrisno, Mojoagung, Jombang',
            -7.5585, 112.3510, 'S1', 'Unipdu Jombang', 'Guru les privat SD selama 3 tahun.',
            ['Mapel Umum' => ['TK', 'SD', 'SMP']], 24);
        $m4 = $this->mentor('Dimas Prasetyo', 'dimas@sec.test', 'Laki-laki', '1999-09-03', 'Jl. Gajah Mada No. 21, Mojoagung, Jombang',
            -7.5650, 112.3380, 'S1', 'Universitas Negeri Surabaya', 'Tentor bimbel SMA, spesialis Matematika dan Fisika.',
            ['Mapel SMA' => ['SMA'], 'Bahasa Inggris' => ['SMP', 'SMA']], 22);
        $m5 = $this->mentor('Siti Aminah', 'siti@sec.test', 'Perempuan', '2001-06-25', 'Desa Gambiran, Mojoagung, Jombang',
            -7.5720, 112.3460, 'D3', 'Politeknik Negeri Malang', 'Bersertifikat instruktur sempoa.',
            ['Sempoa' => ['SD', 'SMP'], 'Mapel Umum' => ['TK', 'SD']], 23);
        $m6 = $this->mentor('Fajar Ramadhan', 'fajar@sec.test', 'Laki-laki', '2002-12-11', 'Desa Betek, Mojoagung, Jombang',
            -7.5500, 112.3300, 'S1', 'Unipdu Jombang', 'Asisten laboratorium komputer kampus.',
            ['Komputer' => ['TK', 'SD', 'SMP', 'SMA'], 'Bahasa Inggris' => ['SD']], 21);
        $this->mentor('Nur Laili', 'laili@sec.test', 'Perempuan', '2003-04-08', 'Desa Karobelah, Mojoagung, Jombang',
            -7.5610, 112.3600, 'S1', 'Unipdu Jombang', null,
            ['Mapel Umum' => ['SD', 'SMP']], 3); // mentor baru, belum mengajar

        // 3. Wali dan murid
        $waliContoh = User::where('email', 'wali@sec.test')->first()->waliMurid;
        $andi  = Murid::where('nama', 'Andi Pratama')->first();
        $salsa = Murid::where('nama', 'Salsa Aulia')->first();

        $w2 = $this->wali('Hendra Wijaya', 'hendra@sec.test', '081332110001', 'Ayah', 'Pegawai Negeri Sipil', 'Perum Mojoagung Indah B-12, Mojoagung', 24);
        $rafi  = $this->murid($w2, 'Rafi Wijaya', 'Laki-laki', '2017-05-10', 'SD kelas 3', 'SDN Mojoagung 2', -7.5632, 112.3445, 'Masih kesulitan membaca cepat.');
        $nayla = $this->murid($w2, 'Nayla Wijaya', 'Perempuan', '2020-11-02', 'TK', 'TK Dharma Wanita Mojoagung', -7.5632, 112.3445, null);

        $w3 = $this->wali('Dewi Lestari', 'dewi@sec.test', '081332110002', 'Ibu', 'Guru', 'Desa Kauman, Mojoagung', 23);
        $kirana = $this->murid($w3, 'Kirana Putri', 'Perempuan', '2011-07-19', 'SMP kelas 9', 'SMPN 1 Mojoagung', -7.5598, 112.3402, 'Persiapan ujian akhir SMP.');

        $w4 = $this->wali('Ratna Sari', 'ratna@sec.test', '081332110003', 'Ibu', 'Wiraswasta', 'Jl. Hayam Wuruk No. 5, Mojoagung', 20);
        $bima = $this->murid($w4, 'Bima Saputra', 'Laki-laki', '2009-03-30', 'SMA kelas 11', 'SMAN Mojoagung', -7.5668, 112.3355, null);

        $w5 = $this->wali('Agus Salim', 'agus@sec.test', '081332110004', 'Ayah', 'Petani', 'Desa Mancilan, Mojoagung', 23);
        $zahra = $this->murid($w5, 'Zahra Salsabila', 'Perempuan', '2016-01-14', 'SD kelas 4', 'MI Al-Hikmah Mojoagung', -7.5745, 112.3495, null);
        $fikri = $this->murid($w5, 'Fikri Maulana', 'Laki-laki', '2013-09-22', 'SMP kelas 7', 'SMPN 2 Mojoagung', -7.5745, 112.3495, 'Belum pernah menggunakan komputer.');

        $w6 = $this->wali('Lilis Suryani', 'lilis@sec.test', '081332110005', 'Ibu', 'Karyawan Swasta', 'Desa Miagan, Mojoagung', 15);
        $alya = $this->murid($w6, 'Alya Rahmawati', 'Perempuan', '2014-04-05', 'SD kelas 6', 'SDN Miagan', -7.5520, 112.3530, null);

        $w7 = $this->wali('Joko Santoso', 'joko@sec.test', '081332110006', 'Ayah', 'Wiraswasta', 'Desa Dukuhmojo, Mojoagung', 11);
        $daffa = $this->murid($w7, 'Daffa Pratama', 'Laki-laki', '2010-10-17', 'SMA kelas 10', 'SMAN Mojoagung', -7.5480, 112.3420, null);

        $w8 = $this->wali('Yuni Astuti', 'yuni@sec.test', '081332110007', 'Ibu', null, 'Perum Griya Permata C-3, Mojoagung', 6);
        $keisha = $this->murid($w8, 'Keisha Aurelia', 'Perempuan', '2018-12-01', 'SD kelas 2', 'SDN Mojoagung 1', -7.5690, 112.3470, 'Anak pemalu, perlu pendekatan pelan.');

        // 4. Riwayat kursus.  Sen=1 Sel=2 Rab=3 Kam=4 Jum=5 Sab=6
        // Pilihan 'akhir' (jika semua pertemuan sudah diabsen):
        //   cair | disetujui | diajukan | dikembalikan | belum (belum diajukan) | menunggu_wali | belum_sesuai
        $k = fn (...$a) => $this->kursus(...$a);

        // Andi (SD 5) – Mentor Contoh
        $k($andi, 'Mapel Umum', $m1, [2, 6], '16:00', '17:30', 10, 'cair');
        $k($andi, 'Mapel Umum', $m1, [2, 6], '16:00', '17:30', 5, 'cair');
        // Salsa (SMP 8) – Rina
        $k($salsa, 'Mapel Umum', $m3, [1, 3, 5], '18:30', '20:00', 8, 'cair');
        $k($salsa, 'Mapel Umum', $m3, [1, 3, 5], '18:30', '20:00', 4, 'belum_sesuai');
        // Rafi (SD 3) – Rina
        $k($rafi, 'Mapel Umum', $m3, [1, 4], '15:30', '17:00', 23, 'cair');
        $k($rafi, 'Mapel Umum', $m3, [1, 4], '15:30', '17:00', 18, 'cair');
        $k($rafi, 'Mapel Umum', $m3, [1, 3, 5], '15:30', '17:00', 9, 'cair', [], 'admin');
        $k($rafi, 'Mapel Umum', $m3, [1, 3, 5], '15:30', '17:00', 4, 'disetujui');
        $k($rafi, 'Mapel Umum', $m3, [1, 3, 5], '15:30', '17:00', 0, null);
        // Nayla (TK) – Siti
        $k($nayla, 'Mapel Umum', $m5, [2, 5], '09:00', '10:00', 14, 'cair');
        $k($nayla, 'Mapel Umum', $m5, [2, 5], '09:00', '10:00', 5, 'menunggu_wali');
        // Kirana (SMP 9) – Mentor Contoh dan Dimas
        $k($kirana, 'Mapel Umum', $m1, [1, 2, 4, 6], '19:00', '20:30', 22, 'cair');
        $k($kirana, 'Mapel Umum', $m1, [1, 2, 4, 6], '19:00', '20:30', 17, 'cair', [], 'admin');
        $k($kirana, 'Mapel Umum', $m1, [1, 2, 4, 6], '19:00', '20:30', 12, 'cair', ubah: ['ganti' => [6], 'oleh' => 'mentor']); // ganti hari diajukan mentor
        $k($kirana, 'Mapel Umum', $m1, [1, 2, 4, 6], '19:00', '20:30', 7, 'cair', ubah: ['tambahan' => true]); // keberatan wali -> pertemuan tambahan
        $k($kirana, 'Mapel Umum', $m1, [1, 2, 4, 6], '19:00', '20:30', 2, null, [7]);
        $k($kirana, 'Bahasa Inggris', $m4, [3], '16:00', '17:00', 10, 'cair');
        $k($kirana, 'Bahasa Inggris', $m4, [3], '16:00', '17:00', 5, 'dikembalikan');
        // Bima (SMA 11) – Dimas
        $k($bima, 'Mapel SMA', $m4, [2, 4], '18:30', '20:00', 18, 'cair');
        $k($bima, 'Mapel SMA', $m4, [2, 4], '18:30', '20:00', 13, 'cair', ubah: ['ganti' => [2], 'oleh' => 'mentor']);
        $k($bima, 'Mapel SMA', $m4, [2, 4], '18:30', '20:00', 8, 'cair');
        $k($bima, 'Mapel SMA', $m4, [2, 4], '18:30', '20:00', 4, 'diajukan');
        // Zahra (SD 4) – Siti
        $k($zahra, 'Sempoa', $m5, [1, 3], '15:00', '16:00', 21, 'cair');
        $k($zahra, 'Sempoa', $m5, [1, 3], '15:00', '16:00', 14, 'cair');
        $k($zahra, 'Sempoa', $m5, [1, 3], '15:00', '16:00', 6, 'cair');
        $k($zahra, 'Sempoa', $m5, [1, 3], '15:00', '16:00', 1, null, [3]);
        // Fikri (SMP 7) – Fajar
        $k($fikri, 'Komputer', $m6, [6], '10:00', '11:30', 19, 'cair');
        $k($fikri, 'Komputer', $m6, [6], '10:00', '11:30', 10, 'cair');
        $k($fikri, 'Komputer', $m6, [6], '10:00', '11:30', 6, 'belum');
        // Alya (SD 6) – Fajar
        $k($alya, 'Bahasa Inggris', $m6, [2, 5], '16:00', '17:00', 14, 'cair');
        $k($alya, 'Bahasa Inggris', $m6, [2, 5], '16:00', '17:00', 9, 'cair');
        $k($alya, 'Bahasa Inggris', $m6, [2, 5], '16:00', '17:00', 4, 'cair', ubah: ['ganti' => [3]]); // 1 pertemuan pengganti
        $k($alya, 'Bahasa Inggris', $m6, [2, 5], '16:00', '17:00', 0, null, [2]);
        // Daffa (SMA 10) – Mentor Kedua
        $k($daffa, 'Komputer', $m2, [3, 6], '13:00', '14:30', 10, 'cair');
        $k($daffa, 'Komputer', $m2, [3, 6], '13:00', '14:30', 5, 'cair');
        $k($daffa, 'Komputer', $m2, [3, 6], '13:00', '14:30', 1, null);
        // Keisha (SD 2) – Mentor Contoh
        $k($keisha, 'Mapel Umum', $m1, [1, 4], '15:00', '16:30', 5, 'cair');
        $k($keisha, 'Mapel Umum', $m1, [1, 4], '15:00', '16:30', 1, null);

        // Kursus yang belum memiliki mentor
        $k($keisha, 'Sempoa', null, [3], '16:00', '17:00', -1, null, [], 'wali', 'menunggu');
        $k($zahra, 'Bahasa Inggris', null, [5], '15:00', '16:00', -1, null, [], 'wali', 'tersedia');

        // Pengajuan ganti hari dari mentor yang masih berjalan
        $this->pengajuanGanti($rafi, $m3, 'Menunggu',
            'Ada ujian praktikum di kampus, sudah disepakati dengan Ibu wali untuk pindah ke hari berikutnya.');
        $this->pengajuanGanti($zahra, $m5, 'Ditolak', 'Ada acara keluarga di luar kota.',
            'Wali belum menyetujui tanggal tersebut karena anak ada les mengaji. Silakan sepakati ulang dengan wali.');
    }

    private function kirimPesan(Kursus $kursus, User $user, string $jenis, string $isi, Carbon $waktu): void
    {
        $t = TanggapanVerifikasi::create(['id_kursus' => $kursus->id, 'id_user' => $user->id, 'jenis' => $jenis, 'isi' => $isi]);
        $this->cap($t, $waktu);
    }

    // Pengajuan ganti hari oleh mentor untuk pertemuan berikutnya (belum diabsen)
    private function pengajuanGanti(Murid $murid, Mentor $mentor, string $status, string $alasan, ?string $catatan = null): void
    {
        $kursus = $murid->kursus()->where('id_mentor', $mentor->id)->latest('id')->first();
        $jadwal = $kursus->jadwal()->whereDate('tanggal', '>', $this->hariIni)->orderBy('tanggal')->first();
        if (! $jadwal) {
            return;
        }
        $diajukan = $this->hariIni->copy()->subDay()->setTime(20, 15);
        $p = PengajuanJadwal::create([
            'id_jadwal' => $jadwal->id, 'id_mentor' => $mentor->id,
            'tanggal_baru' => $jadwal->tanggal->copy()->addDay()->toDateString(),
            'jam_mulai' => $jadwal->jam_mulai, 'jam_selesai' => $jadwal->jam_selesai, 'alasan' => $alasan,
            'status' => $status, 'catatan_pengelola' => $catatan,
            'id_pemroses' => $status === 'Menunggu' ? null : $this->admin->id,
            'diproses_pada' => $status === 'Menunggu' ? null : $this->hariIni->copy()->setTime(8, 30),
        ]);
        $this->cap($p, $diajukan);
        if ($status === 'Ditolak') {
            $this->log($this->admin, 'Menolak pengajuan ganti jadwal',
                "Pertemuan ke-{$jadwal->pertemuan_ke} {$kursus->bidang->nama_bidang} - {$murid->nama} (mentor {$mentor->user->nama}). Alasan: {$catatan}",
                $this->hariIni->copy()->setTime(8, 30));
        }
    }

    // ================= Pembantu =================

    // Senin pada $n pekan sebelum pekan ini (negatif = pekan depan)
    private function minggu(int $n): Carbon
    {
        return $this->hariIni->copy()->startOfWeek()->subWeeks($n);
    }

    private function cap($model, Carbon $waktu): void
    {
        $model->timestamps = false;
        $model->forceFill(['created_at' => $waktu, 'updated_at' => $waktu])->save();
        $model->timestamps = true;
    }

    private function mentor($nama, $email, $jk, $lahir, $alamat, $lat, $lng, $pend, $inst, $pengalaman, array $keahlian, int $pekanLalu): Mentor
    {
        $waktu = $this->minggu($pekanLalu)->subDays(3)->setTime(9, 0);
        $user = User::create(['nama' => $nama, 'email' => $email, 'password' => 'mentor12345',
            'no_hp' => '0857' . mt_rand(10000000, 99999999), 'peran' => 'mentor']);
        $mentor = $user->mentor()->create([
            'jenis_kelamin' => $jk, 'tanggal_lahir' => $lahir, 'alamat' => $alamat,
            'latitude' => $lat, 'longitude' => $lng, 'pendidikan_terakhir' => $pend,
            'asal_institusi' => $inst, 'pengalaman_mengajar' => $pengalaman,
        ]);
        $mentor->simpanKeahlian(collect($keahlian)->mapWithKeys(fn ($t, $b) => [$this->bidang[$b]->id => $t])->all());
        $this->cap($user, $waktu);
        $this->cap($mentor, $waktu);
        $this->log($this->owner, 'Menambah mentor', 'Mentor: ' . $nama, $waktu);

        return $mentor->load('keahlian');
    }

    private function wali($nama, $email, $hp, $hubungan, $pekerjaan, $alamat, int $pekanLalu): WaliMurid
    {
        $waktu = $this->minggu($pekanLalu)->subDays(4)->setTime(20, 0);
        $user = User::create(['nama' => $nama, 'email' => $email, 'password' => 'wali12345', 'no_hp' => $hp, 'peran' => 'wali']);
        $wali = $user->waliMurid()->create(['hubungan' => $hubungan, 'pekerjaan' => $pekerjaan, 'alamat' => $alamat]);
        $this->cap($user, $waktu);
        $this->cap($wali, $waktu);

        return $wali;
    }

    private function murid(WaliMurid $wali, $nama, $jk, $lahir, $jenjang, $sekolah, $lat, $lng, $catatan): Murid
    {
        $murid = $wali->murid()->create([
            'nama' => $nama, 'jenis_kelamin' => $jk, 'tanggal_lahir' => $lahir, 'asal_sekolah' => $sekolah,
            'jenjang' => $jenjang, 'alamat' => $wali->alamat . ', Jombang',
            'latitude' => $lat, 'longitude' => $lng, 'catatan_khusus' => $catatan,
        ]);
        $this->cap($murid, Carbon::parse($wali->created_at)->addMinutes(10));

        return $murid;
    }

    private function log(?User $user, string $aktivitas, string $keterangan, Carbon $waktu): void
    {
        $log = LogAktivitas::create(['id_user' => $user?->id, 'aktivitas' => $aktivitas, 'keterangan' => $keterangan]);
        $this->cap($log, $waktu);
    }

    /**
     * Membuat satu kursus beserta jadwal, absensi, verifikasi wali, dan honor.
     * $pekanLalu: kursus dimulai pada pekan ke-n sebelum pekan ini (0 = pekan ini, -1 = pekan depan).
     * $lewati: nomor pertemuan yang sengaja tidak diabsen mentor.
     * $ubah: ['ganti' => [nomor pertemuan dipindah 1 hari], 'oleh' => 'admin' (langsung) atau 'mentor' (pengajuan disetujui admin)].
     */
    private function kursus(Murid $murid, string $namaBidang, ?Mentor $mentor, array $hari, string $mulai, string $selesai,
                            int $pekanLalu, ?string $akhir, array $lewati = [], string $verifikator = 'wali', string $status = 'diambil',
                            array $ubah = []): void
    {
        $bidang  = $this->bidang[$namaBidang];
        $jumlah  = count($hari) * 4;
        $honor   = TarifHonor::hitung($bidang, $murid->tingkat(), $jumlah)['honor'];
        $tglMulai = $this->minggu($pekanLalu);
        $dibuat  = $tglMulai->copy()->subDays(mt_rand(3, 6))->setTime(mt_rand(7, 21), mt_rand(0, 59));

        $kursus = $murid->kursus()->create([
            'id_bidang' => $bidang->id, 'id_mentor' => $mentor?->id, 'jumlah_pertemuan' => $jumlah,
            'honor' => $honor, 'status' => $status,
        ]);
        $kursus->buatJadwal($tglMulai->toDateString(), $hari, $mulai, $selesai);
        $this->cap($kursus, $dibuat);
        $this->log($this->admin, 'Mengonfirmasi kursus', "{$namaBidang} - {$murid->nama} ({$jumlah} pertemuan)", $dibuat->copy()->addHours(2));

        if (! $mentor) {
            return;
        }

        // Jadwal pengganti: langsung oleh admin, atau diajukan mentor lalu disetujui admin
        foreach ($ubah['ganti'] ?? [] as $ke) {
            $j = $kursus->jadwal()->where('pertemuan_ke', $ke)->first();
            $dariMentor = ($ubah['oleh'] ?? 'admin') === 'mentor';
            $alasan = $dariMentor ? 'Ada kegiatan kampus, sudah disepakati dengan wali' : 'Mentor berhalangan, diganti sesuai kesepakatan wali';
            $asal = $j->tanggal->copy();
            $j->update(['tanggal_asal' => $asal, 'tanggal' => $asal->copy()->addDay(),
                'keterangan' => $dariMentor ? $alasan . ' (diajukan mentor)' : $alasan]);
            $uraianGanti = "Pertemuan ke-{$ke} {$namaBidang} - {$murid->nama} dipindah ke " . $j->tanggal->translatedFormat('d M Y');
            if ($dariMentor) {
                $p = PengajuanJadwal::create([
                    'id_jadwal' => $j->id, 'id_mentor' => $mentor->id, 'tanggal_baru' => $j->tanggal->toDateString(),
                    'jam_mulai' => $j->jam_mulai, 'jam_selesai' => $j->jam_selesai, 'alasan' => $alasan,
                    'status' => 'Disetujui', 'id_pemroses' => $this->admin->id, 'diproses_pada' => $asal->copy()->subDay()->setTime(19, 0),
                ]);
                $this->cap($p, $asal->copy()->subDays(2)->setTime(20, 0));
                $this->log($this->admin, 'Menyetujui pengajuan ganti jadwal', $uraianGanti . ". Alasan mentor: {$alasan}", $asal->copy()->subDay()->setTime(19, 0));
            } else {
                $this->log($this->admin, 'Mengganti jadwal pertemuan', $uraianGanti, $asal->copy()->subDay()->setTime(19, 0));
            }
        }

        // Contoh penyelesaian keberatan wali: pertemuan tambahan tanpa biaya setelah pertemuan terakhir
        if (! empty($ubah['tambahan'])) {
            $akhirRutin = $kursus->jadwal()->max('tanggal');
            $tglTambahan = Carbon::parse($akhirRutin)->addDays(3);
            $tambahan = $kursus->jadwal()->create([
                'pertemuan_ke' => $jumlah + 1, 'jenis' => 'tambahan', 'tanggal' => $tglTambahan->toDateString(),
                'jam_mulai' => $mulai, 'jam_selesai' => $selesai,
                'keterangan' => 'Mengulang materi pertemuan terakhir sesuai kesepakatan diskusi verifikasi',
            ]);
            $tglKeberatan = Carbon::parse($akhirRutin)->addDay()->setTime(19, 30);
            $this->kirimPesan($kursus, $murid->wali->user, 'keberatan',
                'Pada pertemuan terakhir mentor hanya membahas sebagian materi karena waktunya habis. Mohon ditindaklanjuti.', $tglKeberatan);
            $this->kirimPesan($kursus, $mentor->user, 'tanggapan',
                'Mohon maaf Bu, materi persiapan ujian memang belum tuntas. Saya bersedia menambah satu pertemuan tanpa biaya.',
                $tglKeberatan->copy()->addHours(2));
            $this->kirimPesan($kursus, $this->admin, 'tanggapan', 'Pertemuan tambahan tanpa biaya dijadwalkan pada '
                . $tglTambahan->translatedFormat('l, d F Y') . ' pukul ' . str_replace(':', '.', $mulai) . '. Alasan: ' . $tambahan->keterangan,
                $tglKeberatan->copy()->addDay()->setTime(9, 0));
            $this->log($this->admin, 'Menambah pertemuan tambahan',
                "{$namaBidang} - {$murid->nama} (mentor {$mentor->user->nama}), pertemuan ke-{$tambahan->pertemuan_ke}",
                $tglKeberatan->copy()->addDay()->setTime(9, 0));
        }

        // Absensi untuk setiap jadwal yang sudah lewat (sebelum hari ini)
        $materi = self::MATERI[$namaBidang];
        foreach ($kursus->jadwal()->orderBy('pertemuan_ke')->get() as $j) {
            if ($j->tanggal->gte($this->hariIni) || in_array($j->pertemuan_ke, $lewati)) {
                continue;
            }
            $tinjau = mt_rand(1, 100) <= 8;                       // sekitar 8% absen di luar 50 m
            $jarak  = $tinjau ? mt_rand(5200, 9600) / 100 : mt_rand(300, 4500) / 100;
            $sudut  = deg2rad(mt_rand(0, 359));
            $waktu  = Carbon::parse($j->tanggal->toDateString() . ' ' . $j->jam_mulai)->addMinutes(mt_rand(-12, 18));
            $catatan = $materi[($j->pertemuan_ke - 1 + $kursus->id) % count($materi)];

            $absen = Absensi::create([
                'id_jadwal'      => $j->id,
                'waktu_absen'    => $waktu,
                'latitude'       => $murid->latitude + ($jarak * cos($sudut)) / 111320,
                'longitude'      => $murid->longitude + ($jarak * sin($sudut)) / (111320 * cos(deg2rad($murid->latitude))),
                'jarak'          => $jarak,
                'foto'           => 'absensi/contoh-swafoto-' . (($this->nomorFoto++ % 4) + 1) . '.svg',
                'catatan_materi' => $catatan,
                'status'         => $tinjau ? 'Perlu Ditinjau' : 'Valid',
            ]);
            $this->cap($absen, $waktu);

            // Sesekali mentor memperbaiki catatan materi
            if (mt_rand(1, 100) <= 6) {
                $riwayat = RiwayatCatatan::create([
                    'id_absensi' => $absen->id,
                    'catatan_lama' => strtok($catatan, ':') . '.',
                    'catatan_baru' => $catatan,
                ]);
                $this->cap($riwayat, $waktu->copy()->addHours(2));
            }
        }

        // Verifikasi wali dan honor hanya jika semua pertemuan sudah diabsen
        $kursus->unsetRelation('jadwal');
        if (! $akhir || ! $kursus->pertemuanLengkap()) {
            return;
        }
        $terakhir = Carbon::parse($kursus->jadwal()->max('tanggal'));
        $batas    = fn (Carbon $t) => $t->gt($this->hariIni) ? $this->hariIni->copy()->setTime(8, 0) : $t;

        if ($akhir === 'menunggu_wali') {
            return;
        }
        $tglVerif = $batas($terakhir->copy()->addDay()->setTime(19, 30));
        $pesan = fn (User $user, string $jenis, string $isi, Carbon $waktu) => $this->kirimPesan($kursus, $user, $jenis, $isi, $waktu);
        if ($akhir === 'belum_sesuai') {
            $alasan = 'Pertemuan hari Jumat terakhir anak sedang sakit, mohon dicek kembali.';
            $kursus->update(['verifikasi_wali' => 'belum_sesuai', 'tanggal_verifikasi' => $tglVerif,
                'alasan_wali' => $alasan, 'id_verifikator' => $murid->wali->id_user]);
            $pesan($murid->wali->user, 'keberatan', $alasan, $tglVerif);
            $pesan($mentor->user, 'tanggapan', "Betul Bu, pada pertemuan terakhir {$murid->nama} kurang sehat sehingga materi hanya diulang ringan. "
                . 'Saya bersedia menambah satu pertemuan untuk membahas ulang materi tersebut tanpa biaya tambahan.',
                $batas($tglVerif->copy()->addDay()->setTime(9, 15)));
            $tglAdmin = $batas($tglVerif->copy()->addDay()->setTime(13, 0));
            $pesan($this->admin, 'tanggapan', 'Admin sudah mengecek absensi pertemuan terakhir (lokasi valid dan swafoto tersedia). '
                . 'Usulan mentor kami setujui, pertemuan tambahan tanpa biaya segera dijadwalkan.', $tglAdmin);
            $this->log($this->admin, 'Menanggapi keberatan wali', "{$namaBidang} - {$murid->nama} (mentor {$mentor->user->nama})", $tglAdmin);
            // Pertemuan tambahan dijadwalkan 2 hari dari sekarang (belum dilaksanakan)
            $tglTambahan = $this->hariIni->copy()->addDays(2);
            $tambahan = $kursus->jadwal()->create([
                'pertemuan_ke' => $jumlah + 1, 'jenis' => 'tambahan', 'tanggal' => $tglTambahan->toDateString(),
                'jam_mulai' => $mulai, 'jam_selesai' => $selesai,
                'keterangan' => 'Mengulang materi pertemuan terakhir karena murid sakit',
            ]);
            $tglJadwalkan = $tglAdmin->copy()->addMinutes(10);
            $pesan($this->admin, 'tanggapan', 'Pertemuan tambahan tanpa biaya dijadwalkan pada '
                . $tglTambahan->translatedFormat('l, d F Y') . ' pukul ' . str_replace(':', '.', $mulai) . '. Alasan: ' . $tambahan->keterangan, $tglJadwalkan);
            $this->log($this->admin, 'Menambah pertemuan tambahan',
                "{$namaBidang} - {$murid->nama} (mentor {$mentor->user->nama}), pertemuan ke-{$tambahan->pertemuan_ke}", $tglJadwalkan);
            return;
        }
        $oleh = $verifikator === 'admin' ? $this->admin : $murid->wali->user;
        $kursus->update(['verifikasi_wali' => 'disetujui', 'tanggal_verifikasi' => $tglVerif, 'id_verifikator' => $oleh->id]);
        $pesan($oleh, 'persetujuan', $verifikator === 'admin'
            ? 'Diverifikasi atas nama wali. Wali mengonfirmasi lewat WhatsApp.' : 'Wali menyetujui seluruh pertemuan.', $tglVerif);
        if ($verifikator === 'admin') {
            $this->log($this->admin, 'Memverifikasi pertemuan atas nama wali',
                "{$namaBidang} - {$murid->nama}. Keterangan: wali mengonfirmasi lewat WhatsApp", $tglVerif);
        }
        if ($akhir === 'belum') {
            return;
        }

        $tglAju  = $batas($tglVerif->copy()->addDay()->setTime(10, 0));
        $uraian  = "{$mentor->user->nama} - {$namaBidang} {$murid->nama}";
        $pengajuan = PengajuanHonor::create([
            'id_mentor' => $mentor->id, 'id_kursus' => $kursus->id, 'tanggal_pengajuan' => $tglAju->toDateString(),
            'video' => 'https://drive.google.com/file/d/contoh' . $kursus->id . '/view',
            'status' => match ($akhir) { 'cair' => 'Dicairkan', 'disetujui' => 'Disetujui', 'dikembalikan' => 'Dikembalikan', default => 'Diajukan' },
            'alasan_pengembalian' => $akhir === 'dikembalikan'
                ? 'Video belum memperlihatkan kegiatan belajar. Mohon unggah ulang video yang lebih jelas.' : null,
        ]);
        $this->cap($pengajuan, $tglAju);

        if ($akhir === 'dikembalikan') {
            $this->log($this->admin, 'Mengembalikan pengajuan honor', $uraian, $batas($tglAju->copy()->addDay()));
            return;
        }
        if (in_array($akhir, ['disetujui', 'cair'])) {
            $this->log($this->admin, 'Menyetujui pengajuan honor', $uraian, $batas($tglAju->copy()->addDay()->setTime(9, 0)));
        }
        if ($akhir === 'cair') {
            $tglCair = $batas($tglAju->copy()->addDays(mt_rand(2, 4))->setTime(13, 0));
            $bukti = 'bukti/contoh-bukti-' . $pengajuan->id . '.svg';
            $nominal = $kursus->honor;
            Storage::disk('public')->put($bukti, $this->svgBukti($nominal, $tglCair, $mentor->user->nama));
            $cair = $pengajuan->pencairan()->create([
                'tanggal_pencairan' => $tglCair->toDateString(), 'nominal' => $nominal, 'bukti_transfer' => $bukti,
            ]);
            $this->cap($cair, $tglCair);
            $this->log($this->owner, 'Mencairkan honor', $uraian . ' sebesar Rp' . number_format($nominal, 0, ',', '.'), $tglCair);
        }
    }

    // Gambar contoh swafoto (SVG, tidak memerlukan ekstensi GD)
    private function siapkanGambar(): void
    {
        $warna = ['#6d3fd1', '#2f8f83', '#c26a12', '#2348c4'];
        foreach ($warna as $i => $w) {
            Storage::disk('public')->put('absensi/contoh-swafoto-' . ($i + 1) . '.svg', <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300">
  <rect width="400" height="300" fill="#e9e6f2"/>
  <rect y="210" width="400" height="90" fill="#d8d3e6"/>
  <circle cx="200" cy="120" r="48" fill="{$w}" opacity=".85"/>
  <path d="M110 300c0-60 40-95 90-95s90 35 90 95z" fill="{$w}" opacity=".85"/>
  <text x="200" y="285" font-family="sans-serif" font-size="16" text-anchor="middle" fill="#fff">Swafoto contoh (data dummy)</text>
</svg>
SVG);
        }
    }

    private function svgBukti(int $nominal, Carbon $tanggal, string $nama): string
    {
        $rp  = 'Rp' . number_format($nominal, 0, ',', '.');
        $tgl = $tanggal->translatedFormat('d F Y');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 420">
  <rect width="360" height="420" rx="16" fill="#fff" stroke="#ddd"/>
  <rect width="360" height="70" rx="16" fill="#2348c4"/>
  <text x="180" y="44" font-family="sans-serif" font-size="20" fill="#fff" text-anchor="middle">Transfer Berhasil</text>
  <text x="180" y="130" font-family="sans-serif" font-size="30" font-weight="bold" fill="#1a2238" text-anchor="middle">{$rp}</text>
  <text x="30" y="190" font-family="sans-serif" font-size="14" fill="#666">Tanggal</text>
  <text x="330" y="190" font-family="sans-serif" font-size="14" fill="#1a2238" text-anchor="end">{$tgl}</text>
  <text x="30" y="225" font-family="sans-serif" font-size="14" fill="#666">Penerima</text>
  <text x="330" y="225" font-family="sans-serif" font-size="14" fill="#1a2238" text-anchor="end">{$nama}</text>
  <text x="30" y="260" font-family="sans-serif" font-size="14" fill="#666">Keterangan</text>
  <text x="330" y="260" font-family="sans-serif" font-size="14" fill="#1a2238" text-anchor="end">Honor mentor SEC</text>
  <text x="180" y="390" font-family="sans-serif" font-size="12" fill="#999" text-anchor="middle">Bukti transfer contoh (data dummy)</text>
</svg>
SVG;
    }
}
