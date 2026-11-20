<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    protected $fillable = ['id_user', 'aktivitas', 'keterangan'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Mencatat aktivitas akun yang sedang login, contoh:
    // LogAktivitas::catat('Menambah mentor', 'Mentor: Budi Santoso');
    public static function catat(string $aktivitas, ?string $keterangan = null): void
    {
        self::create([
            'id_user'    => Auth::id(),
            'aktivitas'  => $aktivitas,
            'keterangan' => $keterangan,
        ]);
    }
}