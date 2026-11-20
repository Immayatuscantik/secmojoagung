<?php

namespace App\Support;

use App\Models\Bidang;
use App\Models\Kursus;
use App\Models\Murid;
use App\Models\TarifHonor;
use Illuminate\Support\Facades\DB;

// Pendaftaran kursus beserta jadwal otomatis.
// Dipakai oleh wali murid (status awal "menunggu") dan oleh owner/admin (status awal "tersedia").
class PendaftaranKursus
{
    const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

    public static function aturan(bool $wajib = true): array
    {
        $syarat = $wajib ? 'required' : 'required_with:id_bidang';

        return [
            'id_bidang'     => ($wajib ? 'required' : 'nullable') . '|exists:bidang,id',
            'hari'          => "{$syarat}|array|min:1|max:5",
            'hari.*'        => 'integer|between:1,6|distinct',
            'jam_mulai'     => "{$syarat}|date_format:H:i",
            'jam_selesai'   => "{$syarat}|date_format:H:i|after:jam_mulai",
            'tanggal_mulai' => "{$syarat}|date|after_or_equal:today",
        ];
    }

    public static function pesan(): array
    {
        return [
            'id_bidang.required'           => 'Kursus wajib dipilih.',
            'hari.required'                => 'Pilih minimal satu hari belajar.',
            'hari.required_with'           => 'Pilih minimal satu hari belajar.',
            'hari.max'                     => 'Maksimal 5 hari belajar per pekan.',
            'jam_mulai.required'           => 'Jam mulai wajib diisi.',
            'jam_mulai.required_with'      => 'Jam mulai wajib diisi.',
            'jam_selesai.required'         => 'Jam selesai wajib diisi.',
            'jam_selesai.required_with'    => 'Jam selesai wajib diisi.',
            'jam_selesai.after'            => 'Jam selesai harus setelah jam mulai.',
            'tanggal_mulai.required'       => 'Tanggal mulai belajar wajib diisi.',
            'tanggal_mulai.required_with'  => 'Tanggal mulai belajar wajib diisi.',
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
        ];
    }

    // Pilihan bidang untuk form: setiap bidang membawa daftar paket dan tingkat yang tersedia.
    // Jika $murid diisi, hanya bidang yang tersedia untuk jenjang murid itu yang ditampilkan.
    public static function pilihanBidang(?Murid $murid = null)
    {
        return Bidang::with('tarifHonor')->orderBy('nama_bidang')->get()
            ->map(function ($b) use ($murid) {
                $tarif = $murid ? TarifHonor::untuk($b->id, $murid->tingkat()) : $b->tarifHonor;
                if ($tarif->isEmpty()) {
                    return null;
                }
                $b->paket   = $tarif->where('jenis', 'paket')->pluck('jumlah_pertemuan')->unique()->sort()->join(',');
                $b->tingkat = implode(',', $b->tingkatTersedia());
                return $b;
            })
            ->filter()->values();
    }

    // Menyimpan kursus dan jadwalnya. Hasil: ['kursus' => Kursus] atau ['galat' => pesan].
    public static function simpan(Murid $murid, array $data, string $status): array
    {
        // Paket 1 bulan: jumlah pertemuan = jumlah hari per pekan x 4 pekan
        $jumlah = count($data['hari']) * 4;
        $bidang = Bidang::find($data['id_bidang']);

        // Honor mentor dihitung dari tarif; jika tidak ada tarif yang cocok, pendaftaran ditolak
        $hasil = TarifHonor::hitung($bidang, $murid->tingkat(), $jumlah);
        if (isset($hasil['galat'])) {
            return $hasil;
        }

        $kursus = DB::transaction(function () use ($murid, $data, $jumlah, $hasil, $status) {
            $kursus = $murid->kursus()->create([
                'id_bidang'        => $data['id_bidang'],
                'jumlah_pertemuan' => $jumlah,
                'honor'            => $hasil['honor'],
                'status'           => $status,
            ]);
            $kursus->buatJadwal($data['tanggal_mulai'], $data['hari'], $data['jam_mulai'], $data['jam_selesai']);

            return $kursus;
        });

        return ['kursus' => $kursus->load('bidang')];
    }
}