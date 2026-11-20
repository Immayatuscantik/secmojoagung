<?php

namespace App\Http\Controllers;

use App\Models\Kursus;
use App\Models\LogAktivitas;
use App\Models\TanggapanVerifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Diskusi saat wali menyatakan pertemuan belum sesuai:
// mentor menanggapi, wali membalas, owner/admin dapat menengahi
class TanggapanVerifikasiController extends Controller
{
    public function store(Request $request, Kursus $kursus)
    {
        $user = Auth::user();
        abort_unless(self::bolehMelihat($kursus, $user), 404);

        if (! $kursus->diskusiTerbuka()) {
            return back()->with('gagal', 'Diskusi hanya dapat dilakukan selama verifikasi wali berstatus belum sesuai.');
        }

        $request->validate([
            'id_kursus_tanggapan' => 'required',
            'isi'                 => 'required|string|max:1000',
        ], [
            'isi.required' => 'Tanggapan wajib diisi.',
            'isi.max'      => 'Tanggapan maksimal 1000 karakter.',
        ]);

        TanggapanVerifikasi::create([
            'id_kursus' => $kursus->id,
            'id_user'   => $user->id,
            'jenis'     => 'tanggapan',
            'isi'       => $request->isi,
        ]);

        if ($user->isPengelola()) {
            $kursus->load('murid', 'bidang', 'mentor.user');
            LogAktivitas::catat('Menanggapi keberatan wali',
                "{$kursus->bidang->nama_bidang} - {$kursus->murid->nama} (mentor {$kursus->mentor?->user->nama}): {$request->isi}");
        }

        return back()->with('sukses', 'Tanggapan terkirim.');
    }

    // Yang boleh melihat dan menanggapi: wali pemilik murid, mentor pengajar, owner/admin
    public static function bolehMelihat(Kursus $kursus, $user): bool
    {
        return match ($user->peran) {
            'owner', 'admin' => true,
            'mentor'         => (int) $kursus->id_mentor === (int) $user->mentor?->id,
            'wali'           => (int) $kursus->murid->id_wali === (int) $user->waliMurid?->id,
            default          => false,
        };
    }
}
