@extends('layouts.mentor')
@section('judul', 'Absensi')

@section('isi')
<div>
    <h1>Absensi</h1>
    <p class="sub">Absensi dibuka 15 menit sebelum hingga 30 menit setelah jadwal dimulai.</p>
</div>

<section class="panel">
    <h2>Jadwal hari ini</h2>
    <table class="tabel">
        <thead><tr><th>Murid</th><th>Kursus</th><th>Pertemuan</th><th>Jam</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse ($hariIni as $j)
                <tr>
                    <td>{{ $j->kursus->murid->nama }}</td>
                    <td>{{ $j->kursus->bidang->nama_bidang }}</td>
                    <td>{{ $j->labelKe() }}</td>
                    <td>{{ substr($j->jam_mulai, 0, 5) }}–{{ substr($j->jam_selesai, 0, 5) }}</td>
                    <td>
                        @if ($j->absensi)
                            <span class="label {{ $j->absensi->status === 'Valid' ? 'diambil' : 'tersedia' }}">{{ $j->absensi->status }}</span>
                        @else
                            <span class="sub">Belum diabsen</span>
                        @endif
                    </td>
                    <td>
                        @if ($j->absensi)
                            <button type="button" class="tombol kecil buka-dialog" data-dialog="absensi-{{ $j->absensi->id }}">Detail</button>
                        @else
                            <a href="{{ route('mentor.absensi.form', $j) }}" class="tombol kecil utama">Absen</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="kosong">Tidak ada jadwal mengajar hari ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<section class="panel">
    <h2>Jadwal berikutnya</h2>
    <table class="tabel">
        <thead><tr><th>Tanggal</th><th>Jam</th><th>Murid</th><th>Kursus</th><th>Pertemuan</th></tr></thead>
        <tbody>
            @forelse ($berikutnya as $j)
                <tr>
                    <td>{{ $j->tanggal->translatedFormat('l, d F Y') }}</td>
                    <td>{{ substr($j->jam_mulai, 0, 5) }}–{{ substr($j->jam_selesai, 0, 5) }}</td>
                    <td>{{ $j->kursus->murid->nama }}</td>
                    <td>{{ $j->kursus->bidang->nama_bidang }}</td>
                    <td>{{ $j->labelKe() }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="kosong">Belum ada jadwal berikutnya.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<section class="panel">
    <h2>Riwayat absensi</h2>
    <p class="sub">10 absensi terakhir sebelum hari ini.</p>
    <table class="tabel">
        <thead><tr><th>Waktu absen</th><th>Murid</th><th>Kursus</th><th>Pertemuan</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse ($riwayat as $a)
                <tr>
                    <td>{{ $a->waktu_absen->translatedFormat('d M Y, H.i') }}</td>
                    <td>{{ $a->jadwal->kursus->murid->nama }}</td>
                    <td>{{ $a->jadwal->kursus->bidang->nama_bidang }}</td>
                    <td>{{ $a->jadwal->labelKe() }}</td>
                    <td><span class="label {{ $a->status === 'Valid' ? 'diambil' : 'tersedia' }}">{{ $a->status }}</span></td>
                    <td><button type="button" class="tombol kecil buka-dialog" data-dialog="absensi-{{ $a->id }}">Detail</button></td>
                </tr>
            @empty
                <tr><td colspan="6" class="kosong">Belum ada riwayat absensi.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

{{-- Pop-up detail absensi --}}
@foreach ($hariIni as $j)
    @if ($j->absensi)
        @include('partials.dialog-absensi', [
            'a' => $j->absensi,
            'bisaEdit' => \App\Http\Controllers\AbsensiController::catatanBisaDiubah($j->kursus),
        ])
    @endif
@endforeach
@foreach ($riwayat as $a)
    @include('partials.dialog-absensi', [
        'a' => $a,
        'bisaEdit' => \App\Http\Controllers\AbsensiController::catatanBisaDiubah($a->jadwal->kursus),
    ])
@endforeach
@include('partials.dialog-skrip')
@endsection
