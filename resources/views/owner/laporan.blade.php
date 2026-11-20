@extends('layouts.owner')
@section('judul', 'Laporan')

@section('isi')
@php
    $rp  = fn ($n) => \App\Support\Rupiah::format((int) $n);
    $pct = fn ($n) => str_replace('.', ',', $n) . '%';
    $persen = fn ($a, $b) => \App\Support\Statistik::persen((int) $a, (int) $b);
    $aktifMentor = $rekapMentor->where('target', '>', 0);
    $terbaik  = $aktifMentor->sortByDesc('persen')->first();
    $terendah = $aktifMentor->sortBy('persen')->first();
    $ringkas  = $jenis === 'ringkas';
@endphp

{{-- ===== Kepala laporan dan pilihan periode ===== --}}
<div class="kepala">
    <div>
        <h1>Laporan {{ $ringkas ? 'Ringkas' : 'Detail' }} {{ ucfirst($periode) }}</h1>
        <p class="sub">Periode: <strong>{{ $judul }}</strong> ({{ $awal->translatedFormat('d M Y') }} – {{ $akhir->translatedFormat('d M Y') }})
            · dicetak {{ now()->translatedFormat('d F Y, H.i') }} WIB</p>
        @if ($keteranganFilter)
            <p class="sub">Filter: <strong>{{ $keteranganFilter }}</strong></p>
        @endif
    </div>
</div>

<section class="panel tanpa-cetak">
    <form method="GET" action="{{ route($rute) }}" class="filter-periode" id="form-periode">
        <select name="periode" id="pilih-periode">
            <option value="mingguan" @selected($periode === 'mingguan')>Mingguan</option>
            <option value="bulanan" @selected($periode === 'bulanan')>Bulanan</option>
            <option value="tahunan" @selected($periode === 'tahunan')>Tahunan</option>
        </select>
        <input type="week"   name="minggu" data-periode="mingguan" value="{{ $periode === 'mingguan' ? $nav['nilai'] : now()->isoWeekYear . '-W' . str_pad(now()->isoWeek, 2, '0', STR_PAD_LEFT) }}">
        <input type="month"  name="bulan"  data-periode="bulanan"  value="{{ $periode === 'bulanan' ? $nav['nilai'] : now()->format('Y-m') }}">
        <input type="number" name="tahun"  data-periode="tahunan"  value="{{ $periode === 'tahunan' ? $nav['nilai'] : now()->year }}" min="2000" max="2100">
        <select name="mentor" title="Filter mentor">
            <option value="">Semua mentor</option>
            @foreach ($daftarMentor as $m)
                <option value="{{ $m->id }}" @selected(request('mentor') == $m->id)>{{ $m->user->nama }}</option>
            @endforeach
        </select>
        <select name="bidang" title="Filter bidang">
            <option value="">Semua bidang</option>
            @foreach ($daftarBidang as $b)
                <option value="{{ $b->id }}" @selected(request('bidang') == $b->id)>{{ $b->nama_bidang }}</option>
            @endforeach
        </select>
        <button type="submit" class="tombol kecil utama">Tampilkan</button>
        @if ($keteranganFilter)
            <a href="{{ route($rute, ['periode' => $periode] + ($periode === 'mingguan' ? ['minggu' => $nav['nilai']] : ($periode === 'tahunan' ? ['tahun' => $nav['nilai']] : ['bulan' => $nav['nilai']]))) }}" class="tombol kecil">Hapus filter</a>
        @endif
        <div class="ml-auto flex flex-wrap gap-2">
            <a href="{{ route($rute, $nav['sebelum']) }}" class="tombol kecil">&laquo; Sebelumnya</a>
            <a href="{{ route($rute, $nav['sesudah']) }}" class="tombol kecil">Berikutnya &raquo;</a>
            <button type="button" class="tombol kecil" id="tombol-cetak">Cetak</button>
        </div>
    </form>
</section>

<nav class="tab tanpa-cetak" id="tab-laporan">
    @foreach (['ringkasan' => 'Ringkasan', 'mentor' => 'Rekap Mentor', 'murid' => 'Rekap Murid', 'honor' => 'Rekap Honor', 'lain' => 'Informasi Lain'] as $kunci => $nama)
        <a href="#" data-tab="{{ $kunci }}" class="{{ $tab === $kunci ? 'aktif' : '' }}">{{ $nama }}</a>
    @endforeach
</nav>

{{-- ===================== TAB 1: RINGKASAN ===================== --}}
<div data-isi-tab="ringkasan" class="isi-tab">
    <h2 class="judul-cetak">1. Ringkasan</h2>

    <div class="statistik">
        <div class="kartu-stat">
            <span>Ketercapaian pertemuan</span>
            <strong>{{ $pct($ringkasan['persen']) }}</strong>
            <small>{{ $ringkasan['terpenuhi'] }} dari {{ $ringkasan['target'] }} pertemuan</small>
        </div>
        <div class="kartu-stat peringatan">
            <span>Tidak absen</span>
            <strong>{{ $ringkasan['tidak_absen'] }}</strong>
            <small>{{ $pct($persen($ringkasan['tidak_absen'], $ringkasan['target'])) }} dari target</small>
        </div>
        <div class="kartu-stat">
            <span>Absensi tepat lokasi</span>
            <strong>{{ $pct($ringkasan['persen_valid']) }}</strong>
            <small>{{ $ringkasan['valid'] }} Valid, {{ $ringkasan['tinjau'] }} Perlu Ditinjau</small>
        </div>
        <div class="kartu-stat">
            <span>Murid / mentor aktif</span>
            <strong>{{ $ringkasan['murid_aktif'] }} / {{ $ringkasan['mentor_aktif'] }}</strong>
            <small>{{ $ringkasan['murid_baru'] }} murid baru, {{ $ringkasan['kursus_baru'] }} kursus baru</small>
        </div>
        <div class="kartu-stat">
            <span>Honor kursus selesai</span>
            <strong class="nominal">{{ $rp($ringkasan['hak_honor']) }}</strong>
            <small>{{ $rp($ringkasan['hak_cair']) }} sudah cair, {{ $rp($ringkasan['hak_belum']) }} belum</small>
        </div>
        <div class="kartu-stat">
            <span>Dana honor dicairkan</span>
            <strong class="nominal">{{ $rp($ringkasan['cair_periode']) }}</strong>
            <small>{{ $ringkasan['cair_jumlah'] }} kali pencairan pada periode ini</small>
        </div>
    </div>

    <section class="panel">
        <h2>Kesimpulan periode {{ $judul }}</h2>
        <ul class="daftar-kesimpulan">
            <li>Dari <strong>{{ $ringkasan['target'] }}</strong> pertemuan yang dijadwalkan, <strong>{{ $ringkasan['terpenuhi'] }}</strong> terpenuhi
                (<strong>{{ $pct($ringkasan['persen']) }}</strong>), {{ $ringkasan['tidak_absen'] }} tidak diabsen, dan {{ $ringkasan['belum'] }} belum dilaksanakan.</li>
            <li>{{ $ringkasan['ganti'] }} pertemuan diganti ke hari lain (jadwal pengganti).</li>
            <li>{{ $ringkasan['absensi'] }} absensi tercatat; <strong>{{ $pct($ringkasan['persen_valid']) }}</strong> dilakukan tepat di lokasi (≤ 50 m).</li>
            @if ($terbaik)
                <li>Ketercapaian tertinggi: <strong>{{ $terbaik->mentor->user->nama }}</strong> ({{ $pct($terbaik->persen) }})@if ($aktifMentor->count() > 1);
                    terendah: <strong>{{ $terendah->mentor->user->nama }}</strong> ({{ $pct($terendah->persen) }})@endif.</li>
            @endif
            <li>{{ $ringkasan['kursus_selesai'] }} kursus selesai pada periode ini dengan total honor {{ $rp($ringkasan['hak_honor']) }};
                <strong>{{ $rp($ringkasan['hak_belum']) }}</strong> belum dicairkan.</li>
            <li>{{ $mentorTidakAktif->count() }} mentor tidak mengajar dan {{ $jobTersedia->count() }} kursus masih mencari mentor.</li>
        </ul>
        <progress class="progres" max="100" value="{{ $ringkasan['persen'] }}"></progress>
    </section>
</div>

{{-- ===================== TAB 2: REKAP MENTOR ===================== --}}
<div data-isi-tab="mentor" class="isi-tab">
    <h2 class="judul-cetak">2. Rekap Mentor</h2>
    @if ($ringkas)
    <section class="panel">
        <h2>Ketercapaian pertemuan per mentor</h2>
        <div class="gulir-tabel">
        <table class="tabel tabel-rekap">
            <thead><tr><th>Mentor</th><th>Target</th><th>Terpenuhi</th><th>Ketercapaian</th></tr></thead>
            <tbody>
                @foreach ($rekapMentor as $r)
                    <tr>
                        <td><strong>{{ $r->mentor->user->nama }}</strong></td>
                        <td>{{ $r->target }}</td>
                        <td>{{ $r->terpenuhi }}</td>
                        <td>
                            @if ($r->target > 0)
                                <strong>{{ $pct($r->persen) }}</strong>
                                <progress class="progres" max="100" value="{{ $r->persen }}"></progress>
                            @else
                                <span class="sub">Tidak mengajar</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><th>Total</th><th>{{ $ringkasan['target'] }}</th><th>{{ $ringkasan['terpenuhi'] }}</th><th>{{ $pct($ringkasan['persen']) }}</th></tr>
            </tfoot>
        </table>
        </div>
    </section>
    @else
    <section class="panel">
        <h2>Rekap pertemuan dan honor per mentor</h2>
        <p class="sub">Pertemuan dihitung dari jadwal dalam periode. Honor dihitung dari kursus yang pertemuan terakhirnya berada dalam periode.</p>
        <div class="gulir-tabel">
        <table class="tabel tabel-rekap">
            <thead>
                <tr>
                    <th>Mentor</th><th>Murid</th><th>Target</th><th>Terpenuhi</th><th>Tidak absen</th><th>Diganti</th>
                    <th>Ketercapaian</th><th>Valid / Ditinjau</th><th>Hak honor</th><th>Sudah cair</th><th>Belum cair</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rekapMentor as $r)
                    <tr>
                        <td><strong>{{ $r->mentor->user->nama }}</strong><br><span class="sub">{{ $r->mentor->ringkasanKeahlian() }}</span></td>
                        <td>{{ $r->murid }}</td>
                        <td>{{ $r->target }}</td>
                        <td>{{ $r->terpenuhi }}</td>
                        <td>{{ $r->tidak_absen }}</td>
                        <td>{{ $r->ganti }}</td>
                        <td>
                            @if ($r->target > 0)
                                <strong>{{ $pct($r->persen) }}</strong>
                                <progress class="progres" max="100" value="{{ $r->persen }}"></progress>
                            @else
                                <span class="sub">Tidak mengajar</span>
                            @endif
                        </td>
                        <td>{{ $r->valid }} / {{ $r->tinjau }}</td>
                        <td>{{ $rp($r->hak) }}</td>
                        <td>{{ $rp($r->cair) }}</td>
                        <td>{{ $rp($r->hak - $r->cair) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th>{{ $ringkasan['murid_aktif'] }}</th>
                    <th>{{ $ringkasan['target'] }}</th>
                    <th>{{ $ringkasan['terpenuhi'] }}</th>
                    <th>{{ $ringkasan['tidak_absen'] }}</th>
                    <th>{{ $ringkasan['ganti'] }}</th>
                    <th>{{ $pct($ringkasan['persen']) }}</th>
                    <th>{{ $rekapMentor->sum('valid') }} / {{ $rekapMentor->sum('tinjau') }}</th>
                    <th>{{ $rp($rekapMentor->sum('hak')) }}</th>
                    <th>{{ $rp($rekapMentor->sum('cair')) }}</th>
                    <th>{{ $rp($rekapMentor->sum('hak') - $rekapMentor->sum('cair')) }}</th>
                </tr>
            </tfoot>
        </table>
        </div>
    </section>

    <section class="panel">
        <h2>Rincian murid yang diajar setiap mentor</h2>
        @forelse ($aktifMentor as $r)
            <details class="rincian-mentor" open>
                <summary>{{ $r->mentor->user->nama }} · {{ $r->kursus->count() }} kursus · ketercapaian {{ $pct($r->persen) }}</summary>
                <table class="tabel jarak-atas-kiri">
                    <thead>
                        <tr><th>Murid</th><th>Kursus</th><th>Pertemuan periode ini</th><th>Progres paket</th><th>Status honor</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($r->kursus as $k)
                            @php [$teksH, $kelasH] = $k->statusHonor(); @endphp
                            <tr>
                                <td>{{ $k->murid->nama }}<br><span class="sub">{{ $k->murid->jenjang }}</span></td>
                                <td>{{ $k->bidang->nama_bidang }}</td>
                                <td>{{ $k->terpenuhi_periode }} / {{ $k->target_periode }} ({{ $pct($persen($k->terpenuhi_periode, $k->target_periode)) }})</td>
                                <td>{{ $k->hadir_count }} / {{ $k->jumlah_pertemuan }} ({{ $pct($persen($k->hadir_count, $k->jumlah_pertemuan)) }})</td>
                                <td><span class="label {{ $kelasH }}">{{ $teksH }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </details>
        @empty
            <p class="kosong-besar">Tidak ada mentor yang mengajar pada periode ini.</p>
        @endforelse
    </section>
    @endif
</div>

{{-- ===================== TAB 3: REKAP MURID ===================== --}}
<div data-isi-tab="murid" class="isi-tab">
    <h2 class="judul-cetak">3. Rekap Murid</h2>
    @if ($ringkas)
    <section class="panel">
        <h2>Pertemuan per murid</h2>
        <p class="sub">{{ $kursus->count() }} kursus memiliki jadwal pada periode ini.</p>
        <div class="gulir-tabel">
        <table class="tabel tabel-rekap">
            <thead><tr><th>Murid</th><th>Kursus</th><th>Pertemuan periode ini</th><th>Progres paket</th></tr></thead>
            <tbody>
                @forelse ($kursus as $k)
                    <tr>
                        <td><strong>{{ $k->murid->nama }}</strong></td>
                        <td>{{ $k->bidang->nama_bidang }}</td>
                        <td>{{ $k->terpenuhi_periode }} / {{ $k->target_periode }}</td>
                        <td>
                            {{ $k->hadir_count }} / {{ $k->jumlah_pertemuan }} ({{ $pct($persen($k->hadir_count, $k->jumlah_pertemuan)) }})
                            <progress class="progres" max="{{ $k->jumlah_pertemuan }}" value="{{ $k->hadir_count }}"></progress>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="kosong">Tidak ada kursus yang memiliki jadwal pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </section>
    @else
    <section class="panel">
        <h2>Rekap pertemuan per murid dan kursus</h2>
        <p class="sub">{{ $kursus->count() }} kursus memiliki jadwal pada periode ini.</p>
        <div class="gulir-tabel">
        <table class="tabel tabel-rekap">
            <thead>
                <tr><th>Murid</th><th>Kursus</th><th>Mentor</th><th>Pertemuan periode ini</th><th>Progres paket</th><th>Verifikasi wali</th><th>Status honor</th></tr>
            </thead>
            <tbody>
                @forelse ($kursus as $k)
                    @php
                        [$teksV, $kelasV] = $k->labelVerifikasi();
                        [$teksH, $kelasH] = $k->statusHonor();
                        $pPeriode = $persen($k->terpenuhi_periode, $k->target_periode);
                    @endphp
                    <tr>
                        <td><strong>{{ $k->murid->nama }}</strong><br><span class="sub">{{ $k->murid->jenjang }} · wali {{ $k->murid->wali->user->nama }}</span></td>
                        <td>{{ $k->bidang->nama_bidang }}</td>
                        <td>{{ $k->mentor?->user->nama ?? 'Belum ada mentor' }}</td>
                        <td>
                            {{ $k->terpenuhi_periode }} / {{ $k->target_periode }} · <strong>{{ $pct($pPeriode) }}</strong>
                            @if ($k->tidak_absen_periode)<br><span class="label tidak-hadir">{{ $k->tidak_absen_periode }} tidak absen</span>@endif
                        </td>
                        <td>
                            {{ $k->hadir_count }} / {{ $k->jumlah_pertemuan }} ({{ $pct($persen($k->hadir_count, $k->jumlah_pertemuan)) }})
                            <progress class="progres" max="{{ $k->jumlah_pertemuan }}" value="{{ $k->hadir_count }}"></progress>
                        </td>
                        <td>@if ($k->id_mentor)<span class="label {{ $kelasV }}">{{ $teksV }}</span>@else - @endif</td>
                        <td>@if ($k->id_mentor)<span class="label {{ $kelasH }}">{{ $teksH }}</span>@else - @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="kosong">Tidak ada kursus yang memiliki jadwal pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </section>
    @endif
</div>

{{-- ===================== TAB 4: REKAP HONOR ===================== --}}
<div data-isi-tab="honor" class="isi-tab">
    <h2 class="judul-cetak">4. Rekap Honor</h2>
    @if ($ringkas)
    <section class="panel">
        <h2>Honor mentor per bulan</h2>
        <p class="sub">Dihitung dari kursus yang pertemuan terakhirnya jatuh pada bulan tersebut.</p>
        <div class="gulir-tabel">
        <table class="tabel tabel-rekap">
            <thead><tr><th>Bulan</th><th>Total honor</th><th>Sudah cair</th><th>Belum cair</th></tr></thead>
            <tbody>
                @foreach ($honorBulanan as $h)
                    <tr>
                        <td><strong>{{ $h->bulan }}</strong><br><span class="sub">{{ $h->kursus }} kursus berakhir</span></td>
                        <td>{{ $rp($h->total) }}</td>
                        <td>{{ $rp($h->cair) }}</td>
                        <td>{{ $rp($h->total - $h->cair) }}</td>
                    </tr>
                @endforeach
            </tbody>
            @if ($honorBulanan->count() > 1)
                <tfoot>
                    <tr><th>Total</th><th>{{ $rp($honorBulanan->sum('total')) }}</th><th>{{ $rp($honorBulanan->sum('cair')) }}</th>
                        <th>{{ $rp($honorBulanan->sum('total') - $honorBulanan->sum('cair')) }}</th></tr>
                </tfoot>
            @endif
        </table>
        </div>
    </section>
    @else
    @php $perStatus = $kursusBerakhir->groupBy(fn ($k) => $k->statusHonor()[0]); @endphp
    <div class="statistik">
        <div class="kartu-stat"><span>Total hak honor</span><strong class="nominal">{{ $rp($ringkasan['hak_honor']) }}</strong>
            <small>{{ $kursusBerakhir->count() }} kursus berakhir</small></div>
        <div class="kartu-stat"><span>Sudah dicairkan</span><strong class="nominal">{{ $rp($ringkasan['hak_cair']) }}</strong>
            <small>{{ $pct($persen($ringkasan['hak_cair'], $ringkasan['hak_honor'])) }} dari total</small></div>
        <div class="kartu-stat peringatan"><span>Belum dicairkan</span><strong class="nominal">{{ $rp($ringkasan['hak_belum']) }}</strong>
            <small>{{ $kursusBerakhir->where('sudah_cair', false)->count() }} kursus</small></div>
    </div>

    <section class="panel">
        <h2>Honor per kursus yang berakhir pada periode ini</h2>
        <p class="sub">Rincian per status:
            @forelse ($perStatus as $status => $daftar)
                <span class="label">{{ $status }}: {{ $daftar->count() }} ({{ $rp($daftar->sum('nominal_honor')) }})</span>
            @empty
                -
            @endforelse
        </p>
        <div class="gulir-tabel">
        <table class="tabel tabel-rekap">
            <thead>
                <tr><th>Pertemuan terakhir</th><th>Mentor</th><th>Murid</th><th>Kursus</th><th>Pertemuan</th><th>Honor</th><th>Status</th><th>Tanggal cair</th></tr>
            </thead>
            <tbody>
                @forelse ($kursusBerakhir as $k)
                    @php [$teksH, $kelasH] = $k->statusHonor(); @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($k->tanggal_akhir)->translatedFormat('d M Y') }}</td>
                        <td>{{ $k->mentor->user->nama }}</td>
                        <td>{{ $k->murid->nama }}</td>
                        <td>{{ $k->bidang->nama_bidang }}</td>
                        <td>{{ $k->hadir_count }} / {{ $k->jumlah_pertemuan }}</td>
                        <td>{{ $rp($k->nominal_honor) }}</td>
                        <td><span class="label {{ $kelasH }}">{{ $teksH }}</span></td>
                        <td>{{ $k->pengajuanTerakhir()?->pencairan?->tanggal_pencairan?->translatedFormat('d M Y') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="kosong">Tidak ada kursus yang berakhir pada periode ini.</td></tr>
                @endforelse
            </tbody>
            @if ($kursusBerakhir->isNotEmpty())
                <tfoot>
                    <tr><th colspan="5">Total</th><th>{{ $rp($ringkasan['hak_honor']) }}</th>
                        <th colspan="2">Cair {{ $rp($ringkasan['hak_cair']) }} · Belum {{ $rp($ringkasan['hak_belum']) }}</th></tr>
                </tfoot>
            @endif
        </table>
        </div>
    </section>

    <section class="panel">
        <h2>Pencairan dana honor pada periode ini</h2>
        <p class="sub">Transaksi pencairan berdasarkan tanggal transfer (termasuk honor kursus dari periode sebelumnya).</p>
        <table class="tabel">
            <thead><tr><th>Tanggal</th><th>Mentor</th><th>Murid</th><th>Kursus</th><th>Nominal</th></tr></thead>
            <tbody>
                @forelse ($pencairan as $c)
                    @php $p = $c->pengajuanHonor; @endphp
                    <tr>
                        <td>{{ $c->tanggal_pencairan->translatedFormat('d M Y') }}</td>
                        <td>{{ $p->mentor->user->nama }}</td>
                        <td>{{ $p->kursus->murid->nama }}</td>
                        <td>{{ $p->kursus->bidang->nama_bidang }} ({{ $p->kursus->jumlah_pertemuan }} pertemuan)</td>
                        <td>{{ $rp($c->nominal) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="kosong">Belum ada pencairan pada periode ini.</td></tr>
                @endforelse
            </tbody>
            @if ($pencairan->isNotEmpty())
                <tfoot><tr><th colspan="4">Total</th><th>{{ $rp($pencairan->sum('nominal')) }}</th></tr></tfoot>
            @endif
        </table>
    </section>
    @endif
</div>

{{-- ===================== TAB 5: INFORMASI LAIN ===================== --}}
<div data-isi-tab="lain" class="isi-tab">
    <h2 class="judul-cetak">5. Informasi Lain</h2>
    <div class="dua-kolom-sama">
        <section class="panel">
            <h2>Mentor yang tidak mengajar</h2>
            <table class="tabel">
                <thead><tr><th>Mentor</th><th>Kontak</th><th>Job sesuai yang tersedia</th></tr></thead>
                <tbody>
                    @forelse ($mentorTidakAktif as $r)
                        <tr>
                            <td>{{ $r->mentor->user->nama }}<br><span class="sub">{{ $r->mentor->ringkasanKeahlian() }}</span></td>
                            <td>{{ $r->mentor->user->no_hp }}</td>
                            <td>
                                @if ($r->job_sesuai > 0)
                                    <span class="label tersedia">{{ $r->job_sesuai }} job belum diambil</span>
                                @else
                                    <span class="sub">Tidak ada</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="kosong">Semua mentor mengajar pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="panel">
            <h2>Pendaftaran kursus per bidang</h2>
            <p class="sub">Kursus yang didaftarkan pada periode ini.</p>
            <table class="tabel">
                <thead><tr><th>Bidang</th><th>Jumlah</th><th>Persentase</th></tr></thead>
                <tbody>
                    @forelse ($pendaftaranBidang as $nama => $jumlah)
                        <tr>
                            <td>{{ $nama }}</td>
                            <td>{{ $jumlah }}</td>
                            <td>{{ $pct($persen($jumlah, $pendaftaranBidang->sum())) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="kosong">Tidak ada pendaftaran kursus pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>

    <section class="panel">
        <h2>Murid yang belum mendapat mentor</h2>
        <p class="sub">Kondisi saat laporan dibuka.</p>
        <table class="tabel">
            <thead><tr><th>Murid</th><th>Kursus</th><th>Wali</th><th>Rencana mulai</th><th>Lama menunggu</th></tr></thead>
            <tbody>
                @forelse ($jobTersedia as $k)
                    @php $mulai = $k->jadwal->sortBy('pertemuan_ke')->first()?->tanggal; @endphp
                    <tr>
                        <td>{{ $k->murid->nama }}<br><span class="sub">{{ $k->murid->jenjang }}</span></td>
                        <td>{{ $k->bidang->nama_bidang }}</td>
                        <td>{{ $k->murid->wali->user->nama }}<br><span class="sub">{{ $k->murid->wali->user->no_hp }}</span></td>
                        <td>
                            {{ $mulai?->translatedFormat('d M Y') ?? '-' }}
                            @if ($mulai && $mulai->isPast() && ! $mulai->isToday())
                                <br><span class="label tidak-hadir">Sudah lewat</span>
                            @endif
                        </td>
                        <td>{{ (int) $k->created_at->diffInDays(now()) }} hari</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="kosong">Semua kursus sudah memiliki mentor.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="panel">
        <h2>Absensi yang perlu ditinjau</h2>
        <table class="tabel">
            <thead><tr><th>Waktu</th><th>Mentor</th><th>Murid</th><th>Jarak</th><th class="tanpa-cetak">Aksi</th></tr></thead>
            <tbody>
                @forelse ($absensiTinjau as $a)
                    <tr>
                        <td>{{ $a->waktu_absen->translatedFormat('d M Y, H.i') }}</td>
                        <td>{{ $a->jadwal->kursus->mentor?->user->nama ?? '-' }}</td>
                        <td>{{ $a->jadwal->kursus->murid->nama }}</td>
                        <td>{{ number_format($a->jarak, 0, ',', '.') }} m</td>
                        <td class="tanpa-cetak"><a href="{{ route('owner.absensi.show', $a) }}" class="tombol kecil">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="kosong">Tidak ada absensi yang perlu ditinjau pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection

@push('script')
<script>
    // Tab laporan (saat dicetak, semua tab ditampilkan)
    const tombolTab = document.querySelectorAll('#tab-laporan a');
    function bukaTab(nama) {
        tombolTab.forEach(t => t.classList.toggle('aktif', t.dataset.tab === nama));
        document.querySelectorAll('.isi-tab').forEach(s => s.classList.toggle('tersembunyi', s.dataset.isiTab !== nama));
    }
    tombolTab.forEach(t => t.addEventListener('click', e => { e.preventDefault(); bukaTab(t.dataset.tab); }));
    bukaTab(document.querySelector('#tab-laporan a.aktif')?.dataset.tab || 'ringkasan');

    // Hanya tampilkan isian periode yang dipilih (minggu / bulan / tahun)
    const pilihPeriode = document.getElementById('pilih-periode');
    function aturIsian() {
        document.querySelectorAll('#form-periode [data-periode]').forEach(el => {
            const tampil = el.dataset.periode === pilihPeriode.value;
            el.classList.toggle('tersembunyi', !tampil);
            el.disabled = !tampil;
        });
    }
    pilihPeriode.addEventListener('change', aturIsian);
    aturIsian();

    document.getElementById('tombol-cetak').addEventListener('click', () => window.print());
</script>
@endpush
