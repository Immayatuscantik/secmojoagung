<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaliMurid extends Model
{
    protected $table = 'wali_murid';

    protected $fillable = ['id_user', 'hubungan', 'pekerjaan', 'alamat'];

    // Profil wali dimiliki oleh satu akun
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Satu wali dapat mendaftarkan lebih dari satu murid
    public function murid()
    {
        return $this->hasMany(Murid::class, 'id_wali');
    }
}