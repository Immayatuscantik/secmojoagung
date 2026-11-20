@extends('layouts.owner')
@section('judul', 'Detail Mentor')

@section('isi')
<div class="kepala">
    <div>
        <h1>{{ $mentor->user->nama }}</h1>
        <p class="sub">Mentor · {{ $mentor->bidang->pluck('nama_bidang')->join(', ') }}</p>
    </div>
    <div class="baris-tombol tanpa-jarak">
        <a href="{{ route('owner.mentor.index') }}" class="tombol">Kembali</a>
        <a href="{{ route('owner.mentor.edit', $mentor) }}" class="tombol utama">Ubah Data</a>
    </div>
</div>

<nav class="tab">
    <a href="{{ route('owner.mentor.show', [$mentor, 'tab' => 'profil']) }}" class="{{ $tab === 'profil' ? 'aktif' : '' }}">Profil Pribadi</a>
    <a href="{{ route('owner.mentor.show', [$mentor, 'tab' => 'bidang']) }}" class="{{ $tab === 'bidang' ? 'aktif' : '' }}">Bidang Dikuasai ({{ $mentor->bidang->count() }})</a>
    <a href="{{ route('owner.mentor.show', [$mentor, 'tab' => 'kursus']) }}" class="{{ $tab === 'kursus' ? 'aktif' : '' }}">Kursus yang Diajar ({{ $mentor->kursus->count() }})</a>
    <a href="{{ route('owner.mentor.show', [$mentor, 'tab' => 'portofolio']) }}" class="{{ $tab === 'portofolio' ? 'aktif' : '' }}">Portofolio</a>
</nav>

@if ($tab === 'profil')
    @include('partials.mentor-profil')
@elseif ($tab === 'bidang')
    @include('partials.mentor-bidang')
@elseif ($tab === 'portofolio')
    @include('partials.portofolio-mentor')
@else
    <section class="panel">
        <h2>Kursus yang diajar</h2>
        <table class="tabel">
            <thead>
                <tr><th>Murid</th><th>Bidang</th><th>Jadwal</th><th>Progres</th><th>Verifikasi wali</th></tr>
            </thead>
            <tbody>
                @forelse ($mentor->kursus as $k)
                    <tr>
                        <td>{{ $k->murid->nama }}<br><span class="sub">{{ $k->murid->jenjang }}</span></td>
                        <td>{{ $k->bidang->nama_bidang }}</td>
                        <td>{{ $k->hariBelajar() }}<br><span class="sub">{{ $k->jamBelajar() }}</span></td>
                        <td>
                            {{ $k->hadir_count }} / {{ $k->jumlah_pertemuan }} pertemuan
                            <progress class="progres" max="{{ $k->jumlah_pertemuan }}" value="{{ $k->hadir_count }}"></progress>
                        </td>
                        <td>
                            @switch($k->verifikasi_wali)
                                @case('disetujui')    <span class="label diambil">Disetujui</span> @break
                                @case('belum_sesuai') <span class="label tidak-hadir">Belum sesuai</span> @break
                                @default              <span class="sub">Belum</span>
                            @endswitch
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="kosong">Mentor ini belum mengambil kursus.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endif
@endsection