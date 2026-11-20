<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Kursus;
use App\Support\Haversine;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AbsensiController extends Controller
{
    const RADIUS        = 50;  // meter: jarak <= 50 m -> Valid (R4)
    const RADIUS_MAKS   = 100; // meter: 50-100 m -> Perlu Ditinjau (R5), > 100 m -> ditolak
    const BUKA_SEBELUM  = 15;  // menit sebelum jam mulai (R2)
    const TUTUP_SESUDAH = 30;  // menit sesudah jam mulai (R2)

    // Jadwal hari ini, jadwal berikutnya, dan riwayat absensi milik mentor
    public function index()
    {
        $milikSaya = fn ($q) => $q->where('id_mentor', $this->idMentor());

        $hariIni = Jadwal::whereHas('kursus', $milikSaya)
            ->whereDate('tanggal', today())
            ->with(['kursus.murid', 'kursus.bidang', 'absensi.riwayatCatatan'])
            ->orderBy('jam_mulai')
            ->get();

        $berikutnya = Jadwal::whereHas('kursus', $milikSaya)
            ->whereDate('tanggal', '>', today())
            ->doesntHave('absensi')
            ->with(['kursus.murid', 'kursus.bidang'])
            ->orderBy('tanggal')->orderBy('jam_mulai')
            ->limit(5)
            ->get();

        $riwayat = Absensi::whereHas('jadwal.kursus', $milikSaya)
            ->whereDate('waktu_absen', '<', today())
            ->with(['jadwal.kursus.murid', 'jadwal.kursus.bidang', 'riwayatCatatan'])
            ->latest('waktu_absen')
            ->limit(10)
            ->get();

        return view('mentor.absensi.index', compact('hariIni', 'berikutnya', 'riwayat'));
    }

    // Halaman absensi untuk satu jadwal
    public function create(Jadwal $jadwal)
    {
        $this->pastikanMilikMentor($jadwal);
        [$buka, $tutup] = $this->rentang($jadwal);

        return view('mentor.absensi.form', [
            'jadwal'  => $jadwal->load('kursus.murid', 'kursus.bidang', 'absensi'),
            'buka'    => $buka,
            'tutup'   => $tutup,
            'terbuka' => now()->between($buka, $tutup),
            'radius'  => self::RADIUS,
            'radiusMaks' => self::RADIUS_MAKS,
        ]);
    }

    // Menyimpan absensi
    public function store(Request $request, Jadwal $jadwal)
    {
        $this->pastikanMilikMentor($jadwal);

        // R6: satu jadwal hanya dapat diabsen satu kali
        if ($jadwal->absensi()->exists()) {
            return redirect()->route('mentor.absensi')->with('gagal', 'Jadwal ini sudah diabsen.');
        }

        // R2: rentang waktu absensi
        [$buka, $tutup] = $this->rentang($jadwal);
        if (! now()->between($buka, $tutup)) {
            return back()->with('gagal', 'Absensi hanya dapat dilakukan pukul '
                . $buka->format('H.i') . '–' . $tutup->format('H.i') . '.');
        }

        // R3: lokasi dan swafoto wajib ada
        $data = $request->validate([
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'foto'           => ['required', 'string', 'regex:/^data:image\/jpeg;base64,/'],
            'catatan_materi' => 'required|string|max:1000',
        ], [
            'latitude.required'       => 'Lokasi belum diperoleh. Izinkan akses lokasi pada browser.',
            'longitude.required'      => 'Lokasi belum diperoleh. Izinkan akses lokasi pada browser.',
            'foto.required'           => 'Swafoto wajib diambil. Izinkan akses kamera pada browser.',
            'foto.regex'              => 'Format swafoto tidak sesuai.',
            'catatan_materi.required' => 'Catatan materi wajib diisi.',
        ]);

        // R4, R5: hitung jarak dengan rumus Haversine
        $murid = $jadwal->kursus->murid;
        $jarak = Haversine::jarak($data['latitude'], $data['longitude'], $murid->latitude, $murid->longitude);

        // Lebih dari 100 m dari rumah murid: absensi ditolak
        if ($jarak > self::RADIUS_MAKS) {
            return back()->with('gagal', 'Absensi ditolak karena lokasi Anda ' . number_format($jarak, 0, ',', '.')
                . ' m dari rumah murid. Absensi hanya dapat dilakukan dalam radius ' . self::RADIUS_MAKS . ' m.');
        }
        $status = $jarak <= self::RADIUS ? 'Valid' : 'Perlu Ditinjau';

        // Menyimpan swafoto
        $biner = base64_decode(substr($data['foto'], strpos($data['foto'], ',') + 1), true);
        if ($biner === false || strlen($biner) > 2 * 1024 * 1024) {
            return back()->with('gagal', 'Swafoto tidak valid. Silakan ambil ulang.');
        }
        $path = 'absensi/' . Str::random(40) . '.jpg';
        Storage::disk('public')->put($path, $biner);

        try {
            Absensi::create([
                'id_jadwal'      => $jadwal->id,
                'waktu_absen'    => now(),
                'latitude'       => $data['latitude'],
                'longitude'      => $data['longitude'],
                'jarak'          => round($jarak, 2),
                'foto'           => $path,
                'catatan_materi' => $data['catatan_materi'],
                'status'         => $status,
            ]);
        } catch (QueryException $e) {
            // Kolom id_jadwal bersifat unik, jadi absensi ganda pasti ditolak basis data (R6)
            Storage::disk('public')->delete($path);
            return redirect()->route('mentor.absensi')->with('gagal', 'Jadwal ini sudah diabsen.');
        }

        return redirect()->route('mentor.absensi')->with('sukses',
            "Absensi tersimpan dengan status {$status} (jarak " . number_format($jarak, 0, ',', '.') . ' m).');
    }

    // Mengubah catatan materi; catatan lama disimpan di riwayat_catatan
    public function ubahCatatan(Request $request, Absensi $absensi)
    {
        $kursus = $absensi->jadwal->kursus;
        abort_unless((int) $kursus->id_mentor === $this->idMentor(), 404);

        if (! self::catatanBisaDiubah($kursus)) {
            return back()->with('gagal', 'Catatan tidak dapat diubah karena honor kursus ini sudah diajukan.');
        }

        $data = $request->validate([
            'catatan_materi' => 'required|string|max:1000',
        ], [
            'catatan_materi.required' => 'Catatan materi wajib diisi.',
            'catatan_materi.max'      => 'Catatan materi maksimal 1000 karakter.',
        ]);

        $baru = trim($data['catatan_materi']);
        if ($baru === $absensi->catatan_materi) {
            return back()->with('gagal', 'Catatan materi tidak berubah.');
        }

        DB::transaction(function () use ($absensi, $baru) {
            $absensi->riwayatCatatan()->create([
                'catatan_lama' => $absensi->catatan_materi,
                'catatan_baru' => $baru,
            ]);
            $absensi->update(['catatan_materi' => $baru]);
        });

        return back()->with('sukses', 'Catatan materi pertemuan ke-' . $absensi->jadwal->pertemuan_ke . ' berhasil diubah.');
    }

    // Catatan dikunci jika honor kursus sudah diajukan (kecuali pengajuan dikembalikan owner)
    public static function catatanBisaDiubah(Kursus $kursus): bool
    {
        return ! $kursus->pengajuanHonor()->where('status', '!=', 'Dikembalikan')->exists();
    }

    // Rentang waktu absensi: 15 menit sebelum s.d. 30 menit sesudah jam mulai
    private function rentang(Jadwal $jadwal): array
    {
        $mulai = Carbon::parse($jadwal->tanggal->toDateString() . ' ' . $jadwal->jam_mulai);

        return [
            $mulai->copy()->subMinutes(self::BUKA_SEBELUM),
            $mulai->copy()->addMinutes(self::TUTUP_SESUDAH),
        ];
    }

    private function idMentor(): int
    {
        return (int) Auth::user()->mentor->id;
    }

    private function pastikanMilikMentor(Jadwal $jadwal): void
    {
        abort_if((int) $jadwal->kursus->id_mentor !== $this->idMentor(), 403);
    }
}
