<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifHonor extends Model
{
    protected $table = 'tarif_honor';

    protected $fillable = ['id_bidang', 'tingkat', 'jenis', 'jumlah_pertemuan', 'nominal'];

    const TINGKAT = ['TK', 'SD', 'SMP', 'SMA', 'Semua'];

    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'id_bidang');
    }

    // Tarif yang berlaku untuk suatu bidang pada tingkat tertentu (termasuk tarif "Semua")
    public static function untuk(int $idBidang, string $tingkat)
    {
        return self::where('id_bidang', $idBidang)
            ->whereIn('tingkat', [$tingkat, 'Semua'])
            ->orderBy('jumlah_pertemuan')
            ->get();
    }

    // Menghitung honor mentor untuk satu paket kursus.
    // Mengembalikan ['honor' => angka] jika berhasil, atau ['galat' => pesan] jika tidak ada tarif yang cocok.
    public static function hitung(Bidang $bidang, string $tingkat, int $jumlahPertemuan): array
    {
        $tarif = self::untuk($bidang->id, $tingkat);

        if ($tarif->isEmpty()) {
            return ['galat' => "Kursus {$bidang->nama_bidang} tidak tersedia untuk jenjang {$tingkat}."];
        }

        // Tarif paket: jumlah pertemuan harus sama dengan salah satu paket
        $paket = $tarif->where('jenis', 'paket');
        if ($paket->isNotEmpty()) {
            $cocok = $paket->firstWhere('jumlah_pertemuan', $jumlahPertemuan);
            if (! $cocok) {
                $pilihan = $paket->pluck('jumlah_pertemuan')->join(', ', ' atau ');
                return ['galat' => "Kursus {$bidang->nama_bidang} hanya tersedia dalam paket {$pilihan} pertemuan."];
            }
            return ['honor' => $cocok->nominal];
        }

        // Tarif per pertemuan: honor = tarif x jumlah pertemuan
        return ['honor' => $tarif->first()->nominal * $jumlahPertemuan];
    }

    public function keterangan(): string
    {
        return $this->jenis === 'paket'
            ? "Paket {$this->jumlah_pertemuan} pertemuan"
            : 'Per pertemuan';
    }
}