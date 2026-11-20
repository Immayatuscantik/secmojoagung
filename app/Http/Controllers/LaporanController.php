<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Bidang;
use App\Models\Kursus;
use App\Models\Mentor;
use App\Models\Murid;
use App\Models\Pencairan;
use App\Support\Statistik;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    // Laporan Detail: seluruh kolom rekap
    public function detail(Request $request)
    {
        return $this->tampil($request, 'detail');
    }

    // Laporan Ringkas: informasi pokok saja (kolom lebih sedikit, honor per bulan)
    public function ringkas(Request $request)
    {
        return $this->tampil($request, 'ringkas');
    }

    // Laporan per periode (mingguan, bulanan, tahunan) untuk owner/admin
    private function tampil(Request $request, string $jenis)
    {
        $request->validate([
            'periode' => 'nullable|in:mingguan,bulanan,tahunan',
            'minggu'  => ['nullable', 'regex:/^\d{4}-W\d{2}$/'],
            'bulan'   => 'nullable|date_format:Y-m',
            'tahun'   => 'nullable|integer|between:2000,2100',
            'mentor'  => 'nullable|integer|exists:mentor,id',
            'bidang'  => 'nullable|integer|exists:bidang,id',
        ]);

        // ===== 1. Menentukan rentang tanggal periode =====
        $periode = $request->periode ?? 'bulanan';
        [$awal, $akhir, $judul, $nav] = $this->rentang($periode, $request);

        // Filter mentor/bidang ikut dibawa saat pindah periode (Sebelumnya/Berikutnya)
        $saringan = array_filter($request->only('mentor', 'bidang'));
        $nav['sebelum'] += $saringan;
        $nav['sesudah'] += $saringan;
        $saring = fn ($q) => $this->saring($q, $request);

        $dalamPeriode = fn ($tanggal) => $tanggal && $tanggal->between($awal, $akhir);

        // ===== 2. Kursus yang memiliki jadwal di dalam periode =====
        $kursus = Kursus::whereHas('jadwal', fn ($q) => $q->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()]))
            ->where($saring)
            ->with(['murid.wali.user', 'bidang', 'mentor.user', 'jadwal.absensi', 'pengajuanHonor.pencairan', 'verifikator'])
            ->get()
            ->each(function ($k) use ($dalamPeriode) {
                $k->hadir_count = $k->jadwal->filter(fn ($j) => ! $j->tambahan() && $j->absensi)->count(); // progres paket (pertemuan rutin)
                $jadwalPeriode  = $k->jadwal->filter(fn ($j) => $dalamPeriode($j->tanggal))
                    ->each(fn ($j) => $j->status_rekap = $k->id_mentor ? Statistik::statusJadwal($j) : 'tanpa_mentor')
                    ->sortBy('tanggal')->values();
                $k->ganti_periode       = $jadwalPeriode->whereNotNull('tanggal_asal')->count();
                $k->target_periode      = $jadwalPeriode->count();
                $k->terpenuhi_periode   = $jadwalPeriode->filter(fn ($j) => $j->absensi)->count();
                $k->tidak_absen_periode = $k->id_mentor
                    ? $jadwalPeriode->filter(fn ($j) => Statistik::statusJadwal($j) === 'tidak_absen')->count() : 0;
                $k->valid_periode  = $jadwalPeriode->filter(fn ($j) => $j->absensi?->status === 'Valid')->count();
                $k->tinjau_periode = $jadwalPeriode->filter(fn ($j) => $j->absensi?->status === 'Perlu Ditinjau')->count();
            })
            ->sortBy(fn ($k) => $k->murid->nama)
            ->values();

        $kursusBermentor = $kursus->whereNotNull('id_mentor');

        // ===== 3. Kursus yang berakhir (pertemuan terakhir) di dalam periode -> hak honor mentor =====
        $kursusBerakhir = Kursus::whereNotNull('id_mentor')
            ->where($saring)
            ->withMax('jadwal as tanggal_akhir', 'tanggal')
            ->with(['murid', 'bidang', 'mentor.user', 'pengajuanHonor.pencairan', 'verifikator'])
            ->withCount(['jadwal as hadir_count' => fn ($q) => $q->where('jenis', 'rutin')->has('absensi')])
            ->get()
            ->filter(fn ($k) => $dalamPeriode(Carbon::parse($k->tanggal_akhir)))
            ->each(function ($k) {
                $p = $k->pengajuanTerakhir();
                $k->sudah_cair  = $p?->status === 'Dicairkan';
                $k->nominal_honor = $p?->pencairan?->nominal ?? $k->honor;
            })
            ->sortBy('tanggal_akhir')
            ->values();

        // ===== 4. Ringkasan =====
        $absensiPeriode = Absensi::whereBetween('waktu_absen', [$awal, $akhir])
            ->whereHas('jadwal.kursus', $saring)->get();
        $pencairan = Pencairan::whereBetween('tanggal_pencairan', [$awal->toDateString(), $akhir->toDateString()])
            ->whereHas('pengajuanHonor.kursus', $saring)
            ->with(['pengajuanHonor.mentor.user', 'pengajuanHonor.kursus.murid', 'pengajuanHonor.kursus.bidang'])
            ->orderBy('tanggal_pencairan')
            ->get();

        $target    = $kursusBermentor->sum('target_periode');
        $terpenuhi = $kursusBermentor->sum('terpenuhi_periode');
        $ringkasan = [
            'target'        => $target,
            'terpenuhi'     => $terpenuhi,
            'tidak_absen'   => $kursusBermentor->sum('tidak_absen_periode'),
            'belum'         => $target - $terpenuhi - $kursusBermentor->sum('tidak_absen_periode'),
            'ganti'         => $kursusBermentor->sum('ganti_periode'),
            'persen'        => Statistik::persen($terpenuhi, $target),
            'absensi'       => $absensiPeriode->count(),
            'valid'         => $absensiPeriode->where('status', 'Valid')->count(),
            'tinjau'        => $absensiPeriode->where('status', 'Perlu Ditinjau')->count(),
            'murid_aktif'   => $kursusBermentor->pluck('id_murid')->unique()->count(),
            'mentor_aktif'  => $kursusBermentor->pluck('id_mentor')->unique()->count(),
            'murid_baru'    => Murid::whereBetween('created_at', [$awal, $akhir])
                ->when($saringan, fn ($q) => $q->whereHas('kursus', $saring))->count(),
            'kursus_baru'   => Kursus::whereBetween('created_at', [$awal, $akhir])->where($saring)->count(),
            'kursus_selesai' => $kursusBerakhir->filter(fn ($k) => $k->pertemuanLengkap())->count(),
            'cair_periode'  => $pencairan->sum('nominal'),
            'cair_jumlah'   => $pencairan->count(),
            'hak_honor'     => $kursusBerakhir->sum('nominal_honor'),
            'hak_cair'      => $kursusBerakhir->where('sudah_cair', true)->sum('nominal_honor'),
        ];
        $ringkasan['persen_valid'] = Statistik::persen($ringkasan['valid'], $ringkasan['absensi']);
        $ringkasan['hak_belum']    = $ringkasan['hak_honor'] - $ringkasan['hak_cair'];

        // ===== 5. Rekap per mentor =====
        $rekapMentor = Mentor::with(['user', 'bidang', 'keahlian'])
            ->when($request->mentor, fn ($q, $id) => $q->whereKey($id))
            ->when($request->bidang, fn ($q, $id) => $q->whereHas('keahlian', fn ($k) => $k->where('id_bidang', $id)))
            ->get()
            ->map(function ($m) use ($kursusBermentor, $kursusBerakhir) {
                $ajar   = $kursusBermentor->where('id_mentor', $m->id)->values();
                $honor  = $kursusBerakhir->where('id_mentor', $m->id)->values();
                $target = $ajar->sum('target_periode');

                return (object) [
                    'mentor'      => $m,
                    'kursus'      => $ajar,
                    'murid'       => $ajar->pluck('id_murid')->unique()->count(),
                    'target'      => $target,
                    'terpenuhi'   => $ajar->sum('terpenuhi_periode'),
                    'tidak_absen' => $ajar->sum('tidak_absen_periode'),
                    'ganti'       => $ajar->sum('ganti_periode'),
                    'valid'       => $ajar->sum('valid_periode'),
                    'tinjau'      => $ajar->sum('tinjau_periode'),
                    'persen'      => Statistik::persen($ajar->sum('terpenuhi_periode'), $target),
                    'honor'       => $honor,
                    'hak'         => $honor->sum('nominal_honor'),
                    'cair'        => $honor->where('sudah_cair', true)->sum('nominal_honor'),
                ];
            })
            ->sortBy(fn ($r) => [-$r->target, $r->mentor->user->nama])
            ->values();

        // ===== 5b. Honor per bulan (laporan ringkas) =====
        $honorBulanan = collect();
        for ($b = $awal->copy()->startOfMonth(); $b->lte($akhir); $b->addMonth()) {
            $diBulan = $kursusBerakhir->filter(fn ($k) => Carbon::parse($k->tanggal_akhir)->format('Y-m') === $b->format('Y-m'));
            $honorBulanan->push((object) [
                'bulan'  => $b->translatedFormat('F Y'),
                'kursus' => $diBulan->count(),
                'total'  => $diBulan->sum('nominal_honor'),
                'cair'   => $diBulan->where('sudah_cair', true)->sum('nominal_honor'),
            ]);
        }

        // ===== 6. Informasi lain =====
        $jobTersedia = Kursus::where('status', 'tersedia')
            ->when($request->bidang, fn ($q, $id) => $q->where('id_bidang', $id))
            ->with(['murid.wali.user', 'bidang', 'jadwal'])->oldest()->get();
        if ($request->mentor) { // hanya job yang sesuai keahlian mentor terpilih
            $dipilih = Mentor::with('keahlian')->find($request->mentor);
            $jobTersedia = $jobTersedia->filter(fn ($k) => $dipilih->bisaMengajar($k))->values();
        }
        $mentorTidakAktif = $rekapMentor->where('target', 0)->each(function ($r) use ($jobTersedia) {
            $r->job_sesuai = $jobTersedia->filter(fn ($k) => $r->mentor->bisaMengajar($k))->count();
        });
        $absensiTinjau = Absensi::whereBetween('waktu_absen', [$awal, $akhir])
            ->where('status', 'Perlu Ditinjau')
            ->whereHas('jadwal.kursus', $saring)
            ->with(['jadwal.kursus.murid', 'jadwal.kursus.mentor.user'])
            ->latest('waktu_absen')->get();
        $pendaftaranBidang = Kursus::whereBetween('created_at', [$awal, $akhir])->where($saring)->with('bidang')->get()
            ->groupBy(fn ($k) => $k->bidang->nama_bidang)->map->count()->sortDesc();

        // ===== 7. Pilihan filter =====
        $daftarMentor = Mentor::with('user')->get()->sortBy(fn ($m) => $m->user->nama)->values();
        $daftarBidang = Bidang::orderBy('nama_bidang')->get();
        $keteranganFilter = collect([
            $request->mentor ? 'Mentor: ' . $daftarMentor->firstWhere('id', $request->mentor)->user->nama : null,
            $request->bidang ? 'Bidang: ' . $daftarBidang->firstWhere('id', $request->bidang)->nama_bidang : null,
        ])->filter()->implode(', ');

        return view('owner.laporan', compact(
            'periode', 'awal', 'akhir', 'judul', 'nav', 'ringkasan', 'kursus', 'kursusBerakhir',
            'rekapMentor', 'pencairan', 'jobTersedia', 'mentorTidakAktif', 'absensiTinjau', 'pendaftaranBidang',
            'daftarMentor', 'daftarBidang', 'keteranganFilter', 'honorBulanan', 'jenis'
        ) + [
            'tab'  => $request->tab ?? 'ringkasan',
            'rute' => $jenis === 'ringkas' ? 'owner.laporan.ringkas' : 'owner.laporan',
        ]);
    }

    // Filter laporan per mentor dan/atau per bidang (diterapkan pada query kursus)
    private function saring(Builder $q, Request $request): void
    {
        $q->when($request->mentor, fn ($q, $id) => $q->where('id_mentor', $id))
          ->when($request->bidang, fn ($q, $id) => $q->where('id_bidang', $id));
    }

    // Rentang tanggal, judul, serta tautan periode sebelumnya/berikutnya
    private function rentang(string $periode, Request $request): array
    {
        if ($periode === 'mingguan') {
            [$th, $mg] = $request->minggu ? explode('-W', $request->minggu) : [now()->isoWeekYear, now()->isoWeek];
            $awal  = Carbon::now()->setISODate((int) $th, (int) $mg)->startOfWeek();
            $akhir = $awal->copy()->endOfWeek();
            $judul = 'Minggu ' . $awal->translatedFormat('d M') . ' – ' . $akhir->translatedFormat('d M Y');
            $kode  = fn ($t) => ['periode' => 'mingguan', 'minggu' => $t->isoWeekYear . '-W' . str_pad($t->isoWeek, 2, '0', STR_PAD_LEFT)];
            $nav   = [$kode($awal->copy()->subWeek()), $kode($awal->copy()->addWeek()), $kode($awal)['minggu']];
        } elseif ($periode === 'tahunan') {
            $awal  = Carbon::create((int) ($request->tahun ?? now()->year))->startOfYear();
            $akhir = $awal->copy()->endOfYear();
            $judul = 'Tahun ' . $awal->year;
            $nav   = [['periode' => 'tahunan', 'tahun' => $awal->year - 1], ['periode' => 'tahunan', 'tahun' => $awal->year + 1], $awal->year];
        } else {
            $awal  = Carbon::createFromFormat('Y-m-d', ($request->bulan ?? now()->format('Y-m')) . '-01')->startOfMonth();
            $akhir = $awal->copy()->endOfMonth();
            $judul = $awal->translatedFormat('F Y');
            $nav   = [['periode' => 'bulanan', 'bulan' => $awal->copy()->subMonth()->format('Y-m')],
                      ['periode' => 'bulanan', 'bulan' => $awal->copy()->addMonth()->format('Y-m')], $awal->format('Y-m')];
        }

        return [$awal, $akhir, $judul, ['sebelum' => $nav[0], 'sesudah' => $nav[1], 'nilai' => $nav[2]]];
    }
}
