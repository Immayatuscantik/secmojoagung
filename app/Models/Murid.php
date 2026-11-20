<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Murid extends Model
{
    protected $table = 'murid';

    protected $fillable = [
        'id_wali', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'asal_sekolah',
        'jenjang', 'alamat', 'latitude', 'longitude', 'catatan_khusus',
    ];

    const JENJANG = [
        'TK', 'SD kelas 1', 'SD kelas 2', 'SD kelas 3', 'SD kelas 4', 'SD kelas 5', 'SD kelas 6',
        'SMP kelas 7', 'SMP kelas 8', 'SMP kelas 9', 'SMA kelas 10', 'SMA kelas 11', 'SMA kelas 12',
    ];

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date'];
    }

    // Murid didaftarkan oleh satu wali
    public function wali()
    {
        return $this->belongsTo(WaliMurid::class, 'id_wali');
    }

    // Satu murid dapat mengambil lebih dari satu kursus
    public function kursus()
    {
        return $this->hasMany(Kursus::class, 'id_murid');
    }

    public function umur(): int
    {
        return $this->tanggal_lahir->age;
    }

    // Tingkat sekolah untuk mencari tarif: "SD kelas 5" -> "SD"
    public function tingkat(): string
    {
        return explode(' ', $this->jenjang)[0];
    }

    // Aturan validasi profil pribadi murid (dipakai wali dan owner/admin)
    public static function aturanProfil(): array
    {
        return [
            'nama'           => 'required|string|max:50',
            'jenis_kelamin'  => 'required|in:Laki-laki,Perempuan',
            'tanggal_lahir'  => 'required|date|before:today',
            'asal_sekolah'   => 'nullable|string|max:100',
            'jenjang'        => 'required|in:' . implode(',', self::JENJANG),
            'alamat'         => 'required|string|max:255',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'catatan_khusus' => 'nullable|string|max:500',
        ];
    }

    public static function pesanProfil(): array
    {
        return [
            'required'             => 'Kolom :attribute wajib diisi.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'jenjang.in'           => 'Jenjang tidak valid.',
            'latitude.required'    => 'Lokasi rumah wajib dipilih pada peta.',
            'longitude.required'   => 'Lokasi rumah wajib dipilih pada peta.',
        ];
    }

    public static function namaKolomProfil(): array
    {
        return [
            'nama' => 'nama murid', 'jenis_kelamin' => 'jenis kelamin', 'tanggal_lahir' => 'tanggal lahir',
            'jenjang' => 'jenjang dan kelas', 'alamat' => 'alamat',
        ];
    }
}