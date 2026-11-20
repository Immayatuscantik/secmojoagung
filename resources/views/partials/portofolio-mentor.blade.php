{{-- Portofolio mentor (dipakai halaman mentor dan Detail Mentor milik owner/admin).
     Variabel: $mentor, $bagian, $kursus, $ringkasan, $perBidang, $aktivitas, $honor, $absensi,
               $rute (nama route), $dasar (parameter route dasar), $pengelola (true jika dibuka owner/admin) --}}
@php
    $rp  = fn ($n) => \App\Support\Rupiah::format((int) $n);
    $pct = fn ($n) => str_replace('.', ',', $n) . '%';
    $link = fn ($bagian, $lain = []) => route($rute, $dasar + ['bagian' => $bagian] + $lain);
@endphp

<nav class="tab-status tanpa-cetak">
    <a href="{{ $link('ringkasan') }}" class="{{ $bagian === 'ringkasan' ? 'aktif' : '' }}">Ringkasan</a>
    <a href="{{ $link('murid') }}" class="{{ $bagian === 'murid' ? 'aktif' : '' }}">Murid yang Pernah Diajar ({{ $ringkasan['murid'] }})</a>
    <a href="{{ $link('absensi') }}" class="{{ $bagian === 'absensi' ? 'aktif' : '' }}">Riwayat Absensi ({{ $ringkasan['pertemuan'] }})</a>
    <a href="{{ $link('honor') }}" class="{{ $bagian === 'honor' ? 'aktif' : '' }}">Riwayat Honor</a>
</nav>

@if ($bagian === 'ringkasan')
    {{-- ===================== RINGKASAN ===================== --}}
    <div class="statistik">
        <div class="kartu-stat">
            <span>Pertemuan mengajar</span>
            <strong>{{ $ringkasan['pertemuan'] }}</strong>
            <small>sejak {{ $ringkasan['mulai_mengajar']?->translatedFormat('d M Y') ?? '-' }}</small>
        </div>
        <div class="kartu-stat">
            <span>Tingkat kehadiran</span>
            <strong>{{ $pct($ringkasan['persen_hadir']) }}</strong>
            <small>{{ $ringkasan['tidak_absen'] }} tidak diabsen, {{ $ringkasan['diganti'] }} diganti</small>
        </div>
        <div class="kartu-stat">
            <span>Absensi tepat lokasi</span>
            <strong>{{ $pct($ringkasan['persen_valid']) }}</strong>
            <small>{{ $ringkasan['valid'] }} Valid, {{ $ringkasan['tinjau'] }} Perlu Ditinjau</small>
        </div>
        <div class="kartu-stat">
            <span>Murid / kursus</span>
            <strong>{{ $ringkasan['murid'] }} / {{ $ringkasan['kursus'] }}</strong>
            <small>{{ $ringkasan['kursus_selesai'] }} selesai, {{ $ringkasan['kursus_berjalan'] }} berjalan</small>
        </div>
        <div class="kartu-stat">
            <span>Honor diterima</span>
            <strong class="nominal">{{ $rp($ringkasan['diterima']) }}</strong>
            <small>dari {{ $ringkasan['jumlah_cair'] }} kursus</small>
        </div>
        <div class="kartu-stat peringatan">
            <span>Honor belum diterima</span>
            <strong class="nominal">{{ $rp($ringkasan['diproses'] + $ringkasan['belum_diajukan']) }}</strong>
            <small>{{ $rp($ringkasan['diproses']) }} diproses, {{ $rp($ringkasan['belum_diajukan']) }} belum diajukan</small>
        </div>
    </div>

    <div class="dua-kolom-sama">
        <section class="panel">
            <h2>Rekap per bidang</h2>
            <table class="tabel">
                <thead><tr><th>Bidang</th><th>Kursus</th><th>Murid</th><th>Pertemuan</th><th>Kehadiran</th></tr></thead>
                <tbody>
                    @forelse ($perBidang as $nama => $b)
                        <tr>
                            <td>{{ $nama }}</td>
                            <td>{{ $b['kursus'] }}</td>
                            <td>{{ $b['murid'] }}</td>
                            <td>{{ $b['pertemuan'] }}</td>
                            <td>{{ $pct($b['persen']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="kosong">Belum pernah mengajar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="panel">
            <h2>Aktivitas 6 bulan terakhir</h2>
            <table class="tabel">
                <thead><tr><th>Bulan</th><th>Pertemuan</th><th>Honor dicairkan</th></tr></thead>
                <tbody>
                    @foreach ($aktivitas as $a)
                        <tr>
                            <td>{{ $a['label'] }}</td>
                            <td>{{ $a['pertemuan'] }}</td>
                            <td>{{ $rp($a['honor']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><th>Total</th><th>{{ $aktivitas->sum('pertemuan') }}</th><th>{{ $rp($aktivitas->sum('honor')) }}</th></tr>
                </tfoot>
            </table>
        </section>
    </div>

    <section class="panel">
        <h2>Bidang yang dikuasai</h2>
        <p>{{ $mentor->ringkasanKeahlian() ?: '-' }}</p>
    </section>

@elseif ($bagian === 'murid')
    {{-- ===================== MURID YANG PERNAH DIAJAR ===================== --}}
    <section class="panel">
        <h2>Murid yang pernah diajar</h2>
        <p class="sub">Kehadiran = pertemuan diabsen ÷ jadwal yang sudah lewat. Progres = pertemuan diabsen ÷ jumlah pertemuan paket.</p>
        <div class="gulir-tabel">
        <table class="tabel tabel-rekap">
            <thead>
                <tr><th>Murid</th><th>Kursus</th><th>Periode belajar</th><th>Progres paket</th><th>Kehadiran</th><th>Status</th><th>Verifikasi wali</th></tr>
            </thead>
            <tbody>
                @forelse ($kursus as $k)
                    @php [$teksS, $kelasS] = $k->labelStatus(); [$teksV, $kelasV] = $k->labelVerifikasi(); @endphp
                    <tr>
                        <td><strong>{{ $k->murid->nama }}</strong><br><span class="sub">{{ $k->murid->jenjang }}@if ($k->murid->asal_sekolah) · {{ $k->murid->asal_sekolah }}@endif</span></td>
                        <td>{{ $k->bidang->nama_bidang }}<br><span class="sub">{{ $k->hariBelajar() }}, {{ $k->jamBelajar() }}</span></td>
                        <td>{{ $k->mulai?->translatedFormat('d M Y') }} – {{ $k->akhir?->translatedFormat('d M Y') }}</td>
                        <td>
                            {{ $k->hadir_count }} / {{ $k->jumlah_pertemuan }} ({{ $pct($k->persen_progres) }})
                            <progress class="progres" max="{{ $k->jumlah_pertemuan }}" value="{{ $k->hadir_count }}"></progress>
                        </td>
                        <td>
                            {{ $pct($k->persen_hadir) }}
                            @if ($k->tidak_absen)<br><span class="label tidak-hadir">{{ $k->tidak_absen }} tidak absen</span>@endif
                            @if ($k->diganti)<br><span class="label s-diajukan">{{ $k->diganti }} diganti</span>@endif
                        </td>
                        <td><span class="label {{ $kelasS }}">{{ $teksS }}</span></td>
                        <td><span class="label {{ $kelasV }}">{{ $teksV }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="kosong">Belum pernah mengajar murid.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </section>

@elseif ($bagian === 'absensi')
    {{-- ===================== RIWAYAT ABSENSI ===================== --}}
    <section class="panel">
        <h2>Riwayat absensi</h2>
        <form method="GET" action="{{ route($rute, $dasar) }}" class="cari tanpa-cetak">
            @foreach ($dasar as $kunci => $nilai)
                @if (! is_numeric($kunci) && $kunci !== 'mentor')
                    <input type="hidden" name="{{ $kunci }}" value="{{ $nilai }}">
                @endif
            @endforeach
            <input type="hidden" name="bagian" value="absensi">
            <input type="month" name="bulan" value="{{ request('bulan') }}" title="Kosongkan untuk semua bulan">
            <select name="status">
                <option value="">Semua status</option>
                <option value="Valid" @selected(request('status') === 'Valid')>Valid</option>
                <option value="Perlu Ditinjau" @selected(request('status') === 'Perlu Ditinjau')>Perlu Ditinjau</option>
            </select>
            <button type="submit" class="tombol kecil utama">Tampilkan</button>
            @if (request('bulan') || request('status'))
                <a href="{{ $link('absensi') }}" class="tombol kecil">Reset</a>
            @endif
        </form>
        <p class="sub">{{ $absensi->total() }} absensi ditemukan.</p>

        <table class="tabel">
            <thead><tr><th>Waktu absen</th><th>Murid</th><th>Kursus</th><th>Pertemuan</th><th>Jarak</th><th>Status</th><th class="tanpa-cetak">Aksi</th></tr></thead>
            <tbody>
                @forelse ($absensi as $a)
                    <tr>
                        <td>{{ $a->waktu_absen->translatedFormat('d M Y, H.i') }}</td>
                        <td>{{ $a->jadwal->kursus->murid->nama }}</td>
                        <td>{{ $a->jadwal->kursus->bidang->nama_bidang }}</td>
                        <td>{{ $a->jadwal->labelKe() }}</td>
                        <td>{{ number_format($a->jarak, 0, ',', '.') }} m</td>
                        <td><span class="label {{ $a->status === 'Valid' ? 'diambil' : 'tersedia' }}">{{ $a->status }}</span></td>
                        <td class="tanpa-cetak">
                            @if ($pengelola)
                                <a href="{{ route('owner.absensi.show', $a) }}" class="tombol kecil">Detail</a>
                            @else
                                <button type="button" class="tombol kecil buka-dialog" data-dialog="absensi-{{ $a->id }}">Detail</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="kosong">Tidak ada absensi pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($absensi->hasPages())
            <div class="halaman tanpa-cetak">
                @if ($absensi->onFirstPage())
                    <span class="tombol kecil nonaktif">&laquo; Sebelumnya</span>
                @else
                    <a href="{{ $absensi->previousPageUrl() }}" class="tombol kecil">&laquo; Sebelumnya</a>
                @endif

                <span class="sub">Halaman {{ $absensi->currentPage() }} dari {{ $absensi->lastPage() }}</span>

                @if ($absensi->hasMorePages())
                    <a href="{{ $absensi->nextPageUrl() }}" class="tombol kecil">Berikutnya &raquo;</a>
                @else
                    <span class="tombol kecil nonaktif">Berikutnya &raquo;</span>
                @endif
            </div>
        @endif
    </section>

    @unless ($pengelola)
        @foreach ($absensi as $a)
            @include('partials.dialog-absensi', [
                'a' => $a,
                'bisaEdit' => \App\Http\Controllers\AbsensiController::catatanBisaDiubah($a->jadwal->kursus),
            ])
        @endforeach
        @include('partials.dialog-skrip')
    @endunless

@else
    {{-- ===================== RIWAYAT HONOR ===================== --}}
    <div class="statistik">
        <div class="kartu-stat"><span>Sudah diterima</span><strong class="nominal">{{ $rp($ringkasan['diterima']) }}</strong>
            <small>{{ $ringkasan['jumlah_cair'] }} kursus</small></div>
        <div class="kartu-stat"><span>Sedang diproses</span><strong class="nominal">{{ $rp($ringkasan['diproses']) }}</strong>
            <small>diajukan / disetujui</small></div>
        <div class="kartu-stat peringatan"><span>Belum diajukan</span><strong class="nominal">{{ $rp($ringkasan['belum_diajukan']) }}</strong>
            <small>termasuk yang dikembalikan</small></div>
    </div>

    <section class="panel">
        <h2>Riwayat honor per kursus</h2>
        <p class="sub">Daftar kursus yang seluruh pertemuannya sudah diabsen.</p>
        <div class="gulir-tabel">
        <table class="tabel tabel-rekap">
            <thead>
                <tr><th>Selesai</th><th>Murid</th><th>Kursus</th><th>Honor</th><th>Status</th><th>Tanggal diajukan</th><th>Tanggal cair</th><th class="tanpa-cetak">Bukti</th></tr>
            </thead>
            <tbody>
                @forelse ($honor as $k)
                    @php [$teksH, $kelasH] = $k->statusHonor(); $p = $k->pengajuan_terakhir; @endphp
                    <tr>
                        <td>{{ $k->akhir?->translatedFormat('d M Y') }}</td>
                        <td>{{ $k->murid->nama }}</td>
                        <td>{{ $k->bidang->nama_bidang }}<br><span class="sub">{{ $k->jumlah_pertemuan }} pertemuan</span></td>
                        <td>{{ $rp($k->nominal_honor) }}</td>
                        <td>
                            <span class="label {{ $kelasH }}">{{ $teksH }}</span>
                            @if ($p?->status === 'Dikembalikan')<br><span class="sub">{{ $p->alasan_pengembalian }}</span>@endif
                        </td>
                        <td>{{ $p?->tanggal_pengajuan?->translatedFormat('d M Y') ?? '-' }}</td>
                        <td>{{ $p?->pencairan?->tanggal_pencairan?->translatedFormat('d M Y') ?? '-' }}</td>
                        <td class="tanpa-cetak">
                            @if ($p?->pencairan)
                                <a href="{{ asset('storage/' . $p->pencairan->bukti_transfer) }}" target="_blank" class="tombol kecil">Lihat</a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="kosong">Belum ada kursus yang selesai.</td></tr>
                @endforelse
            </tbody>
            @if ($honor->isNotEmpty())
                <tfoot>
                    <tr><th colspan="3">Total</th><th>{{ $rp($honor->sum('nominal_honor')) }}</th>
                        <th colspan="4">Diterima {{ $rp($ringkasan['diterima']) }}</th></tr>
                </tfoot>
            @endif
        </table>
        </div>
    </section>
@endif
