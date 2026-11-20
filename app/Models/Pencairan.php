<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pencairan extends Model
{
    protected $table = 'pencairan';

    protected $fillable = ['id_pengajuan', 'tanggal_pencairan', 'nominal', 'bukti_transfer'];

    protected function casts(): array
    {
        return ['tanggal_pencairan' => 'date'];
    }

    public function pengajuanHonor()
    {
        return $this->belongsTo(PengajuanHonor::class, 'id_pengajuan');
    }
}