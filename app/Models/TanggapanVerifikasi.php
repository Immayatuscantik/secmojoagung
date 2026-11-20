<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Pesan dalam diskusi verifikasi pertemuan (wali, mentor, owner/admin)
class TanggapanVerifikasi extends Model
{
    protected $table = 'tanggapan_verifikasi';

    protected $fillable = ['id_kursus', 'id_user', 'jenis', 'isi'];

    public function kursus()
    {
        return $this->belongsTo(Kursus::class, 'id_kursus');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Nama peran pengirim untuk ditampilkan
    public function namaPeran(): string
    {
        return match ($this->user->peran) {
            'wali'   => 'Wali murid',
            'mentor' => 'Mentor',
            'admin'  => 'Admin',
            'owner'  => 'Owner',
            default  => '-',
        };
    }
}
