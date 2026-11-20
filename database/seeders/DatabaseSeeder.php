<?php

namespace Database\Seeders;

use App\Models\Bidang;
use App\Models\TarifHonor;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ===== Data bidang/kursus SEC =====
        $bidang = [];
        foreach ([
            'Mapel Umum'     => 'Pendampingan mata pelajaran sekolah untuk TK, SD, dan SMP',
            'Mapel SMA'      => 'Pendampingan mata pelajaran sekolah untuk SMA',
            'Sempoa'         => 'Kursus berhitung dengan sempoa (SD, SMP, SMA)',
            'Bahasa Inggris' => 'Kursus Bahasa Inggris (SD, SMP, SMA)',
            'Komputer'       => 'Kursus komputer untuk semua jenjang',
        ] as $nama => $ket) {
            $bidang[$nama] = Bidang::create(['nama_bidang' => $nama, 'keterangan' => $ket]);
        }

        // ===== Tarif honor mentor (sumber: tabel honor guru SEC) =====
        // Mapel Umum: per paket 8/12/16/20 pertemuan (2-5x per pekan selama 4 pekan)
        $paket = [
            'TK'  => [8 => 150000, 12 => 220000, 16 => 288000, 20 => 350000],
            'SD'  => [8 => 150000, 12 => 220000, 16 => 288000, 20 => 350000],
            'SMP' => [8 => 160000, 12 => 240000, 16 => 320000, 20 => 400000],
        ];
        foreach ($paket as $tingkat => $daftar) {
            foreach ($daftar as $jumlah => $nominal) {
                TarifHonor::create([
                    'id_bidang' => $bidang['Mapel Umum']->id, 'tingkat' => $tingkat,
                    'jenis' => 'paket', 'jumlah_pertemuan' => $jumlah, 'nominal' => $nominal,
                ]);
            }
        }

        // Per pertemuan
        $perPertemuan = [
            ['Mapel SMA', 'SMA', 25000],
            ['Sempoa', 'SD', 20000], ['Sempoa', 'SMP', 25000], ['Sempoa', 'SMA', 25000],
            ['Bahasa Inggris', 'SD', 20000], ['Bahasa Inggris', 'SMP', 25000], ['Bahasa Inggris', 'SMA', 25000],
            ['Komputer', 'Semua', 30000],
        ];
        foreach ($perPertemuan as [$nama, $tingkat, $nominal]) {
            TarifHonor::create([
                'id_bidang' => $bidang[$nama]->id, 'tingkat' => $tingkat,
                'jenis' => 'per_pertemuan', 'jumlah_pertemuan' => null, 'nominal' => $nominal,
            ]);
        }

        // ===== Owner dan admin =====
        User::create([
            'nama' => 'Owner SEC', 'email' => 'owner@sec.test', 'password' => 'owner12345',
            'no_hp' => '081234567890', 'peran' => 'owner',
        ]);

        User::create([
            'nama' => 'Admin SEC', 'email' => 'admin@sec.test', 'password' => 'admin12345',
            'no_hp' => '081234567891', 'peran' => 'admin',
        ]);

        // ===== Mentor 1: Mapel Umum dan Mapel SMA =====
        $mentor1 = User::create([
            'nama' => 'Mentor Contoh', 'email' => 'mentor@sec.test', 'password' => 'mentor12345',
            'no_hp' => '081298765432', 'peran' => 'mentor',
        ])->mentor()->create([
            'jenis_kelamin' => 'Perempuan', 'tanggal_lahir' => '2002-05-14',
            'alamat' => 'Jl. Raya Mojoagung No. 10, Mojoagung, Jombang',
            'latitude' => -7.56000000, 'longitude' => 112.34500000,
            'pendidikan_terakhir' => 'S1', 'asal_institusi' => 'Unipdu Jombang',
            'pengalaman_mengajar' => 'Mengajar les privat SD-SMP selama 2 tahun.',
        ]);
        $mentor1->simpanKeahlian([
            $bidang['Mapel Umum']->id => ['TK', 'SD', 'SMP'],
            $bidang['Mapel SMA']->id  => ['SMA'],
        ]);

        // ===== Mentor 2: Bahasa Inggris, Sempoa, dan Komputer =====
        $mentor2 = User::create([
            'nama' => 'Mentor Kedua', 'email' => 'mentor2@sec.test', 'password' => 'mentor12345',
            'no_hp' => '081298765433', 'peran' => 'mentor',
        ])->mentor()->create([
            'jenis_kelamin' => 'Laki-laki', 'tanggal_lahir' => '2001-11-02',
            'alamat' => 'Desa Kademangan, Mojoagung, Jombang',
            'latitude' => -7.57500000, 'longitude' => 112.32500000,
            'pendidikan_terakhir' => 'S1', 'asal_institusi' => 'Unipdu Jombang',
            'pengalaman_mengajar' => null,
        ]);
        $mentor2->simpanKeahlian([
            $bidang['Bahasa Inggris']->id => ['SD', 'SMP'],
            $bidang['Sempoa']->id         => ['SD'],
            $bidang['Komputer']->id       => ['TK', 'SD', 'SMP', 'SMA'],
        ]);

        // ===== Wali murid beserta dua anaknya =====
        $wali = User::create([
            'nama' => 'Wali Contoh', 'email' => 'wali@sec.test', 'password' => 'wali12345',
            'no_hp' => '081311112222', 'peran' => 'wali',
        ])->waliMurid()->create([
            'hubungan' => 'Ibu', 'pekerjaan' => 'Wiraswasta',
            'alamat' => 'Perum Griya Mojoagung Blok C-5, Mojoagung, Jombang',
        ]);

        $andi = $wali->murid()->create([
            'nama' => 'Andi Pratama', 'jenis_kelamin' => 'Laki-laki', 'tanggal_lahir' => '2015-03-21',
            'asal_sekolah' => 'SDN Mojoagung 1', 'jenjang' => 'SD kelas 5',
            'alamat' => 'Perum Griya Mojoagung Blok C-5, Mojoagung, Jombang',
            'latitude' => -7.56600000, 'longitude' => 112.34200000,
            'catatan_khusus' => 'Perlu pendampingan pada soal cerita.',
        ]);

        $salsa = $wali->murid()->create([
            'nama' => 'Salsa Aulia', 'jenis_kelamin' => 'Perempuan', 'tanggal_lahir' => '2012-08-09',
            'asal_sekolah' => 'SMPN 1 Mojoagung', 'jenjang' => 'SMP kelas 8',
            'alamat' => 'Perum Griya Mojoagung Blok C-5, Mojoagung, Jombang',
            'latitude' => -7.56600000, 'longitude' => 112.34200000,
            'catatan_khusus' => null,
        ]);

        // Jadwal dimulai hari ini supaya mudah diuji
        $hariIni = now()->dayOfWeekIso === 7 ? 1 : now()->dayOfWeekIso;
        $hariLain = $hariIni + 3 > 6 ? $hariIni - 3 : $hariIni + 3;
        $hariKetiga = collect([1, 2, 3, 4, 5, 6])->diff([$hariIni, $hariLain])->first();
        $mulai = now()->toDateString();

        // Andi: Mapel Umum SD, 2x per pekan = 8 pertemuan (honor paket Rp150.000) -> tampil di Daftar Job
        $k1 = $andi->kursus()->create([
            'id_bidang' => $bidang['Mapel Umum']->id, 'jumlah_pertemuan' => 8, 'honor' => 150000, 'status' => 'tersedia',
        ]);
        $k1->buatJadwal($mulai, [$hariIni, $hariLain], '16:00', '17:30');

        // Andi: Bahasa Inggris SD, 1x per pekan = 4 pertemuan (4 x Rp20.000) -> belum dikonfirmasi
        $k2 = $andi->kursus()->create([
            'id_bidang' => $bidang['Bahasa Inggris']->id, 'jumlah_pertemuan' => 4, 'honor' => 80000, 'status' => 'menunggu',
        ]);
        $k2->buatJadwal($mulai, [$hariLain], '15:00', '16:00');

        // Salsa: Mapel Umum SMP, 3x per pekan = 12 pertemuan (honor paket Rp240.000) -> tampil di Daftar Job
        $k3 = $salsa->kursus()->create([
            'id_bidang' => $bidang['Mapel Umum']->id, 'jumlah_pertemuan' => 12, 'honor' => 240000, 'status' => 'tersedia',
        ]);
        $k3->buatJadwal($mulai, [$hariIni, $hariLain, $hariKetiga], '18:30', '20:00');
    }
}