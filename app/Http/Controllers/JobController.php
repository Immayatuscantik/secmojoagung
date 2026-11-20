<?php

namespace App\Http\Controllers;

use App\Models\Kursus;
use App\Support\Haversine;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    // Kecepatan rata-rata sepeda motor untuk perkiraan waktu tempuh (km/jam)
    const KECEPATAN = 30;

    // Daftar job: kursus tersedia yang bidang DAN tingkatnya dikuasai mentor, diurutkan dari yang terdekat
    public function index()
    {
        $mentor = Auth::user()->mentor->load(['bidang', 'keahlian']);

        $job = Kursus::where('status', 'tersedia')
            ->whereNull('id_mentor')
            ->whereIn('id_bidang', $mentor->keahlian->pluck('id_bidang'))
            ->with(['murid', 'bidang', 'jadwal' => fn ($q) => $q->orderBy('pertemuan_ke')])
            ->get()
            ->filter(fn ($k) => $mentor->bisaMengajar($k));

        foreach ($job as $k) {
            $k->jarak = Haversine::jarak($mentor->latitude, $mentor->longitude, $k->murid->latitude, $k->murid->longitude);
            // waktu (menit) = jarak (km) / kecepatan (km/jam) x 60, minimal 1 menit
            $k->waktu = max(1, (int) ceil($k->jarak / 1000 / self::KECEPATAN * 60));
        }
        $job = $job->sortBy('jarak')->values();

        return view('mentor.job', compact('job', 'mentor'));
    }

    // Mengambil job (aturan R1)
    public function ambil(Kursus $kursus)
    {
        $mentor = Auth::user()->mentor->load('keahlian');

        // Mentor hanya bisa mengambil kursus yang bidang dan tingkatnya ia kuasai
        if (! $mentor->bisaMengajar($kursus)) {
            return redirect()->route('mentor.job')
                ->with('gagal', 'Job ini tidak sesuai dengan bidang dan tingkat yang Anda kuasai.');
        }

        // Pemeriksaan ulang dan pengambilan dilakukan dalam satu perintah,
        // sehingga jika dua mentor menekan Ambil Job bersamaan, hanya satu yang berhasil.
        $berhasil = Kursus::where('id', $kursus->id)
            ->where('status', 'tersedia')
            ->whereNull('id_mentor')
            ->update([
                'id_mentor' => $mentor->id,
                'status'    => 'diambil',
            ]);

        if (! $berhasil) {
            return redirect()->route('mentor.job')
                ->with('gagal', 'Job sudah diambil oleh mentor lain.');
        }

        return redirect()->route('mentor.murid')
            ->with('sukses', "Job {$kursus->bidang->nama_bidang} untuk {$kursus->murid->nama} berhasil diambil.");
    }
}