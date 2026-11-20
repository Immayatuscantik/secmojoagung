<?php

namespace App\Http\Controllers;

use App\Models\Kursus;
use Illuminate\Support\Facades\Auth;

class MuridSayaController extends Controller
{
    // Kursus yang sedang diajar mentor beserta progres pertemuannya
    public function index()
    {
        $kursus = Kursus::where('id_mentor', Auth::user()->mentor->id)
            ->with(['murid', 'bidang', 'pengajuanHonor', 'tanggapan.user', 'jadwal' => fn ($q) => $q->orderBy('pertemuan_ke'), 'jadwal.absensi'])
            ->withCount(['jadwal as hadir_count' => fn ($q) => $q->where('jenis', 'rutin')->has('absensi')])
            ->latest()
            ->get();

        return view('mentor.murid', compact('kursus'));
    }

    // Detail kursus: profil singkat murid, jadwal, dan absensi
    public function show(Kursus $kursus)
    {
        abort_unless((int) $kursus->id_mentor === (int) Auth::user()->mentor->id, 404);

        $kursus->load([
            'murid.wali.user',
            'bidang',
            'pengajuanHonor',
            'jadwal' => fn ($q) => $q->orderBy('pertemuan_ke'),
            'jadwal.absensi.riwayatCatatan',
            'jadwal.pengajuanJadwal.pemroses',
            'tanggapan.user',
        ]);
        $kursus->hadir_count = $kursus->jadwal->filter(fn ($j) => ! $j->tambahan() && $j->absensi)->count();

        return view('mentor.murid-show', [
            'kursus'      => $kursus,
            'bisaEdit'    => AbsensiController::catatanBisaDiubah($kursus),
        ]);
    }
}
