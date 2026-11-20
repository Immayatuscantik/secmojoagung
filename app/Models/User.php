<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = ['nama', 'email', 'password', 'no_hp', 'peran'];

    protected $hidden = ['password'];

    // Tabel users tidak memakai kolom remember_token
    protected $rememberTokenName = '';

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    // Akun mentor memiliki satu profil mentor
    public function mentor()
    {
        return $this->hasOne(Mentor::class, 'id_user');
    }

    // Akun wali memiliki satu profil wali murid
    public function waliMurid()
    {
        return $this->hasOne(WaliMurid::class, 'id_user');
    }

    // Aktivitas yang dilakukan akun ini (owner/admin)
    public function logAktivitas()
    {
        return $this->hasMany(LogAktivitas::class, 'id_user');
    }

    public function isOwner(): bool
    {
        return $this->peran === 'owner';
    }

    // Owner dan admin sama-sama pengelola bimbel
    public function isPengelola(): bool
    {
        return in_array($this->peran, ['owner', 'admin']);
    }
}