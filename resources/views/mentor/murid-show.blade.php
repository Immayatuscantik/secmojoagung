@extends('layouts.mentor')
@section('judul', 'Detail Kursus')

@section('isi')
@php
    $murid = $kursus->murid;
    $hadir = $kursus->jadwal->filter(fn ($j) => ! $j->tambahan() && $j->absensi)->count();
    $kursus->jadwal->each->setRelation('kursus', $kursus);
    $riwayatGanti = $kursus->jadwal->flatMap->pengajuanJadwal->sortByDesc('created_at');
@endphp

<div class="kepala">
    <div>
        <h1>{{ $murid->nama }}</h1>
        <p class="sub">{{ $kursus->bidang->nama_bidang }} · {{ $kursus->hariBelajar() }}, {{ $kursus->jamBelajar() }}</p>
    </div>
    <a href="{{ route('mentor.murid') }}" class="tombol">Kembali</a>
</div>

<div class="dua-kolom-sama">
    <section class="panel">
        <h2>Profil murid</h2>
        <dl class="info-lokasi">
            <dt>Jenjang</dt>        <dd>{{ $murid->jenjang }} ({{ $murid->umur() }} tahun)</dd>
            <dt>Asal sekolah</dt>   <dd>{{ $murid->asal_sekolah ?: '-' }}</dd>
            <dt>Catatan khusus</dt> <dd>{{ $murid->catatan_khusus ?: '-' }}</dd>
            <dt>Alamat</dt>         <dd>{{ $murid->alamat }}</dd>
            <dt>Wali</dt>           <dd>{{ $murid->wali->user->nama }} ({{ $murid->wali->hubungan }}) · {{ $murid->wali->user->no_hp }}</dd>
        </dl>
    </section>

    <section class="panel">
        <h2>Progres paket</h2>
        <p><strong>{{ $hadir }} / {{ $kursus->jumlah_pertemuan }}</strong> pertemuan terpenuhi
            @if ($kursus->jadwal->where('jenis', 'tambahan')->isNotEmpty())
                <span class="label tersedia">+{{ $kursus->jadwal->where('jenis', 'tambahan')->count() }} pertemuan tambahan</span>
            @endif</p>
        <progress class="progres" value="{{ $hadir }}" max="{{ $kursus->jumlah_pertemuan }}"></progress>
        <dl class="info-lokasi jarak-atas">
            <dt>Honor paket</dt> <dd>{{ \App\Support\Rupiah::format($kursus->honor) }}</dd>
        </dl>
        @include('partials.status-honor-mentor', ['k' => $kursus])
    </section>
</div>

@if ($kursus->adaDiskusi())
    <section class="panel">
        <h2>Verifikasi wali</h2>
        @if ($kursus->diskusiTerbuka())
            <p class="sub">Wali murid menyatakan pertemuan belum sesuai. Berikan tanggapan agar wali dapat meninjau ulang dan menyetujui.
                Owner dan admin ikut memantau diskusi ini.</p>
        @endif
        @include('partials.diskusi-verifikasi', ['k' => $kursus])
    </section>
@endif

<section class="panel">
    <h2>Jadwal dan absensi</h2>
    <p class="sub">Jika berhalangan, ajukan ganti hari setelah menyepakati jadwal baru dengan wali. Jadwal berubah setelah disetujui admin.</p>
    <div class="gulir-tabel">
    <table class="tabel">
        <thead>
            <tr><th>Ke</th><th>Tanggal</th><th>Jam</th><th>Status</th><th>Catatan materi</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            @foreach ($kursus->jadwal as $j)
                @php $menunggu = $j->pengajuanMenunggu(); @endphp
                <tr>
                    <td>{{ $j->pertemuan_ke }}@if ($j->tambahan())<br><span class="label tersedia">Tambahan</span>@endif</td>
                    <td>
                        {{ $j->tanggal->translatedFormat('D, d M Y') }}
                        @if ($j->tanggal_asal)<br><span class="sub">Pengganti dari {{ $j->tanggal_asal->translatedFormat('d M Y') }}</span>@endif
                        @if ($j->tambahan())<br><span class="sub">Tanpa biaya: {{ $j->keterangan }}</span>@endif
                        @if ($menunggu)<br><span class="label tersedia">Diajukan ke {{ $menunggu->tanggal_baru->translatedFormat('d M Y') }}</span>@endif
                    </td>
                    <td>{{ substr($j->jam_mulai, 0, 5) }}–{{ substr($j->jam_selesai, 0, 5) }}</td>
                    <td>
                        @if ($j->absensi)
                            <span class="label {{ $j->absensi->status === 'Valid' ? 'diambil' : 'tersedia' }}">{{ $j->absensi->status }}</span>
                        @else
                            <span class="sub">Belum diabsen</span>
                        @endif
                    </td>
                    <td>
                        {{ $j->absensi?->catatan_materi ?? '-' }}
                        @if ($j->absensi?->riwayatCatatan->isNotEmpty())
                            <span class="sub">(diedit)</span>
                        @endif
                    </td>
                    <td class="aksi">
                        @if ($j->absensi)
                            <button type="button" class="tombol kecil buka-dialog" data-dialog="absensi-{{ $j->absensi->id }}">Detail</button>
                        @elseif ($menunggu)
                            <form method="POST" action="{{ route('mentor.jadwal.tarik', $menunggu) }}"
                                  onsubmit="return confirm('Tarik kembali pengajuan ganti hari ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="tombol kecil">Tarik Pengajuan</button>
                            </form>
                        @elseif ($j->bisaDiubah())
                            <button type="button" class="tombol kecil buka-dialog" data-dialog="ubah-jadwal-{{ $j->id }}">Ajukan Ganti Hari</button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</section>

@if ($riwayatGanti->isNotEmpty())
    <section class="panel">
        <h2>Riwayat pengajuan ganti hari</h2>
        <div class="gulir-tabel">
        <table class="tabel">
            <thead><tr><th>Ke</th><th>Jadwal diusulkan</th><th>Alasan</th><th>Status</th><th>Catatan admin</th></tr></thead>
            <tbody>
                @foreach ($riwayatGanti as $p)
                    <tr>
                        <td>{{ $kursus->jadwal->firstWhere('id', $p->id_jadwal)->pertemuan_ke }}</td>
                        <td>{{ $p->uraianJadwalBaru() }}<br><span class="sub">Diajukan {{ $p->created_at->translatedFormat('d M Y, H.i') }}</span></td>
                        <td>{{ $p->alasan }}</td>
                        <td><span class="label {{ $p->kelasLabel() }}">{{ $p->status }}</span></td>
                        <td>
                            {{ $p->catatan_pengelola ?? '-' }}
                            @if ($p->pemroses)<br><span class="sub">{{ $p->pemroses->nama }}, {{ $p->diproses_pada->translatedFormat('d M Y, H.i') }}</span>@endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </section>
@endif

@foreach ($kursus->jadwal as $j)
    @if ($j->absensi)
        @include('partials.dialog-absensi', ['a' => $j->absensi])
    @elseif ($j->bisaDiubah() && ! $j->pengajuanMenunggu())
        @include('partials.dialog-ubah-jadwal', ['j' => $j, 'k' => $kursus, 'mode' => 'mentor'])
    @endif
@endforeach
@include('partials.dialog-skrip')
@endsection
