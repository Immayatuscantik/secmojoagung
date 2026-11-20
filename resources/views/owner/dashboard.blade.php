@extends('layouts.owner')
@section('judul', 'Dashboard')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@section('isi')
<div>
    <h1>Dashboard Monitoring</h1>
    <p class="sub">Pantau kehadiran mentor dan aktivitas pembelajaran setiap hari.</p>
</div>

{{-- Ringkasan --}}
<div class="statistik">
    <div class="kartu-stat"><span>Mentor</span><strong>{{ $stat['mentor'] }}</strong></div>
    <div class="kartu-stat"><span>Kursus aktif</span><strong>{{ $stat['kursus_aktif'] }}</strong></div>
    <div class="kartu-stat"><span>Job tersedia</span><strong>{{ $stat['job_tersedia'] }}</strong></div>
    <a href="{{ route('owner.murid.index', ['status' => 'menunggu']) }}" class="kartu-stat">
        <span>Menunggu konfirmasi</span><strong>{{ $stat['menunggu'] }}</strong>
    </a>
    <div class="kartu-stat"><span>Absensi hari ini</span><strong>{{ $stat['absen_hari_ini'] }}</strong></div>
    <a href="{{ route('owner.dashboard', ['status' => 'Perlu Ditinjau']) }}" class="kartu-stat peringatan">
        <span>Perlu ditinjau</span><strong>{{ $stat['perlu_ditinjau'] }}</strong>
    </a>
    <a href="{{ route('owner.jadwal.index') }}" class="kartu-stat {{ $stat['ganti_menunggu'] ? 'peringatan' : '' }}">
        <span>Pengajuan ganti jadwal</span><strong>{{ $stat['ganti_menunggu'] }}</strong>
    </a>
    <a href="#keberatan-wali" class="kartu-stat {{ $keberatan->isNotEmpty() ? 'peringatan' : '' }}">
        <span>Keberatan wali</span><strong>{{ $keberatan->count() }}</strong>
    </a>
</div>

{{-- Keberatan wali: diskusi verifikasi yang belum selesai --}}
@if ($keberatan->isNotEmpty())
    <section class="panel" id="keberatan-wali">
        <h2>Keberatan wali yang belum selesai</h2>
        <p class="sub">Wali menyatakan pertemuan belum sesuai. Pantau tanggapan mentor dan tengahi bila diperlukan.</p>
        <div class="gulir-tabel">
        <table class="tabel">
            <thead><tr><th>Murid / kursus</th><th>Mentor</th><th>Keberatan wali</th><th>Pesan terakhir</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach ($keberatan as $k)
                    @php $akhir = $k->tanggapan->last(); @endphp
                    <tr>
                        <td><strong>{{ $k->murid->nama }}</strong><br><span class="sub">{{ $k->bidang->nama_bidang }} · wali {{ $k->murid->wali->user->nama }}</span></td>
                        <td>{{ $k->mentor?->user->nama }}</td>
                        <td>{{ $k->alasan_wali }}<br><span class="sub">{{ $k->tanggal_verifikasi?->translatedFormat('d M Y, H.i') }}</span></td>
                        <td>
                            @if ($akhir && $akhir->jenis === 'tanggapan')
                                <strong>{{ $akhir->namaPeran() }}:</strong> {{ \Illuminate\Support\Str::limit($akhir->isi, 90) }}
                                <br><span class="sub">{{ $akhir->created_at->translatedFormat('d M Y, H.i') }}</span>
                            @else
                                <span class="label tidak-hadir">Belum ditanggapi mentor</span>
                            @endif
                        </td>
                        <td><a href="{{ route('owner.murid.show', [$k->id_murid, 'tab' => 'kursus']) }}#diskusi-{{ $k->id }}" class="tombol kecil utama">Lihat Diskusi</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </section>
@endif

{{-- Grafik --}}
@php $p = $grafik['pertemuan']; @endphp
<div class="grid-grafik">
    <section class="panel">
        <h2>Kehadiran bulan ini</h2>
        <p class="sub">{{ now()->translatedFormat('F Y') }} · terpenuhi {{ $p['terpenuhi'] }} dari {{ $p['target'] }} pertemuan
            (<strong>{{ str_replace('.', ',', $p['persen']) }}%</strong>)</p>
        <div class="wadah-grafik kecil">
            <canvas id="grafik-pertemuan" data-nilai="{{ json_encode([$p['terpenuhi'], $p['tidak_absen'], $p['belum']]) }}"></canvas>
        </div>
    </section>
    <section class="panel">
        <h2>Target vs terpenuhi per mentor</h2>
        <p class="sub">Jumlah pertemuan bulan {{ now()->translatedFormat('F Y') }}</p>
        <div class="wadah-grafik">
            <canvas id="grafik-mentor"
                    data-label="{{ json_encode($p['perMentor']->pluck('nama')) }}"
                    data-target="{{ json_encode($p['perMentor']->pluck('target')) }}"
                    data-terpenuhi="{{ json_encode($p['perMentor']->pluck('terpenuhi')) }}"></canvas>
        </div>
    </section>
</div>
<div class="grid-grafik sama">
    <section class="panel">
        <h2>Honor dicairkan 6 bulan terakhir</h2>
        <div class="wadah-grafik">
            <canvas id="grafik-honor"
                    data-label="{{ json_encode($grafik['honor']['label']) }}"
                    data-nilai="{{ json_encode($grafik['honor']['nilai']) }}"></canvas>
        </div>
    </section>
    <section class="panel">
        <h2>Jumlah kursus per bidang</h2>
        <div class="wadah-grafik">
            <canvas id="grafik-bidang"
                    data-label="{{ json_encode($grafik['bidang']['label']) }}"
                    data-berjalan="{{ json_encode($grafik['bidang']['berjalan']) }}"
                    data-mencari="{{ json_encode($grafik['bidang']['mencari_mentor']) }}"></canvas>
        </div>
    </section>
</div>

{{-- Jadwal hari ini --}}
<section class="panel">
    <h2>Jadwal mengajar hari ini</h2>
    <p class="sub">{{ today()->translatedFormat('l, d F Y') }}</p>
    <table class="tabel">
        <thead>
            <tr><th>Jam</th><th>Mentor</th><th>Murid</th><th>Pertemuan</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            @forelse ($jadwalHariIni as $j)
                @php
                    $batas = \Carbon\Carbon::parse($j->tanggal->toDateString() . ' ' . $j->jam_mulai)
                        ->addMinutes(\App\Http\Controllers\AbsensiController::TUTUP_SESUDAH);
                @endphp
                <tr>
                    <td>{{ substr($j->jam_mulai, 0, 5) }}–{{ substr($j->jam_selesai, 0, 5) }}</td>
                    <td>{{ $j->kursus->mentor?->user->nama ?? '-' }}</td>
                    <td>{{ $j->kursus->murid->nama }} <span class="sub">({{ $j->kursus->bidang->nama_bidang }})</span></td>
                    <td>{{ $j->labelKe() }}</td>
                    <td>
                        @if ($j->absensi)
                            <span class="label {{ $j->absensi->status === 'Valid' ? 'diambil' : 'tersedia' }}">{{ $j->absensi->status }}</span>
                        @elseif (now()->gt($batas))
                            <span class="label tidak-hadir">Tidak absen</span>
                        @else
                            <span class="sub">Menunggu</span>
                        @endif
                    </td>
                    <td>
                        @if ($j->absensi)
                            <a href="{{ route('owner.absensi.show', $j->absensi) }}" class="tombol kecil">Detail</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="kosong">Tidak ada jadwal mengajar hari ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

{{-- Riwayat absensi --}}
<section class="panel">
    <div class="kepala">
        <h2>Riwayat absensi</h2>
        <form method="GET" action="{{ route('owner.dashboard') }}" class="filter">
            <select name="status">
                <option value="">Semua status</option>
                <option value="Valid" @selected(request('status') === 'Valid')>Valid</option>
                <option value="Perlu Ditinjau" @selected(request('status') === 'Perlu Ditinjau')>Perlu Ditinjau</option>
            </select>
            <input type="date" name="tanggal" value="{{ request('tanggal') }}">
            <button type="submit" class="tombol kecil utama">Terapkan</button>
            @if (request('status') || request('tanggal'))
                <a href="{{ route('owner.dashboard') }}" class="tombol kecil">Reset</a>
            @endif
        </form>
    </div>

    <table class="tabel">
        <thead>
            <tr><th>Waktu absen</th><th>Mentor</th><th>Murid</th><th>Pertemuan</th><th>Jarak</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            @forelse ($absensi as $a)
                <tr>
                    <td>{{ $a->waktu_absen->translatedFormat('d M Y, H.i') }}</td>
                    <td>{{ $a->jadwal->kursus->mentor?->user->nama ?? '-' }}</td>
                    <td>{{ $a->jadwal->kursus->murid->nama }} <span class="sub">({{ $a->jadwal->kursus->bidang->nama_bidang }})</span></td>
                    <td>{{ $a->jadwal->labelKe() }}</td>
                    <td>{{ number_format($a->jarak, 0, ',', '.') }} m</td>
                    <td><span class="label {{ $a->status === 'Valid' ? 'diambil' : 'tersedia' }}">{{ $a->status }}</span></td>
                    <td><a href="{{ route('owner.absensi.show', $a) }}" class="tombol kecil">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="kosong">Belum ada data absensi.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($absensi->hasPages())
        <div class="halaman">
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
@endsection

@push('script')
<script>
    // Warna mengikuti palet sistem
    const WARNA = { utama: '#2348c4', gelap: '#14214a', pudar: '#c9d5f6', peach: '#f0a53a', abu: '#d5dae6' };
    const rupiah = n => 'Rp' + Number(n).toLocaleString('id-ID');
    const ambil = (el, nama) => JSON.parse(el.dataset[nama]);
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
    Chart.defaults.maintainAspectRatio = false;

    // 1. Diagram lingkaran kehadiran bulan ini
    const elPertemuan = document.getElementById('grafik-pertemuan');
    new Chart(elPertemuan, {
        type: 'doughnut',
        data: {
            labels: ['Terpenuhi', 'Tidak absen', 'Belum dilaksanakan'],
            datasets: [{ data: ambil(elPertemuan, 'nilai'), backgroundColor: [WARNA.utama, WARNA.peach, WARNA.abu] }],
        },
        options: { plugins: { legend: { position: 'bottom' } } },
    });

    // 2. Diagram batang target vs terpenuhi per mentor
    const elMentor = document.getElementById('grafik-mentor');
    new Chart(elMentor, {
        type: 'bar',
        data: {
            labels: ambil(elMentor, 'label'),
            datasets: [
                { label: 'Target', data: ambil(elMentor, 'target'), backgroundColor: WARNA.pudar },
                { label: 'Terpenuhi', data: ambil(elMentor, 'terpenuhi'), backgroundColor: WARNA.utama },
            ],
        },
        options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { position: 'bottom' } } },
    });

    // 3. Diagram garis honor dicairkan per bulan
    const elHonor = document.getElementById('grafik-honor');
    new Chart(elHonor, {
        type: 'line',
        data: {
            labels: ambil(elHonor, 'label'),
            datasets: [{
                label: 'Honor dicairkan', data: ambil(elHonor, 'nilai'),
                borderColor: WARNA.gelap, backgroundColor: WARNA.pudar, fill: true, tension: 0.3,
            }],
        },
        options: {
            scales: { y: { beginAtZero: true, ticks: { callback: rupiah } } },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => rupiah(c.parsed.y) } } },
        },
    });

    // 4. Diagram batang bertumpuk jumlah kursus per bidang
    const elBidang = document.getElementById('grafik-bidang');
    new Chart(elBidang, {
        type: 'bar',
        data: {
            labels: ambil(elBidang, 'label'),
            datasets: [
                { label: 'Sedang berjalan', data: ambil(elBidang, 'berjalan'), backgroundColor: WARNA.utama },
                { label: 'Mencari mentor', data: ambil(elBidang, 'mencari'), backgroundColor: WARNA.peach },
            ],
        },
        options: {
            scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } },
            plugins: { legend: { position: 'bottom' } },
        },
    });
</script>
@endpush
