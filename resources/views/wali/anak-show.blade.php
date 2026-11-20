@extends('layouts.wali')
@section('judul', 'Detail Anak')

@section('isi')
<div class="kepala">
    <div>
        <h1>{{ $murid->nama }}</h1>
        <p class="sub">{{ $murid->jenjang }}{{ $murid->asal_sekolah ? ' · ' . $murid->asal_sekolah : '' }}</p>
    </div>
    <div class="baris-tombol tanpa-jarak">
        <a href="{{ route('wali.anak') }}" class="tombol">Kembali</a>
        @if ($tab === 'profil')
            <a href="{{ route('wali.anak.edit', $murid) }}" class="tombol utama">Ubah Profil</a>
        @else
            <a href="{{ route('wali.kursus.create', $murid) }}" class="tombol utama">+ Daftar Kursus</a>
        @endif
    </div>
</div>

<nav class="tab">
    <a href="{{ route('wali.anak.show', [$murid, 'tab' => 'profil']) }}" class="{{ $tab === 'profil' ? 'aktif' : '' }}">Profil Pribadi</a>
    <a href="{{ route('wali.anak.show', [$murid, 'tab' => 'kursus']) }}" class="{{ $tab === 'kursus' ? 'aktif' : '' }}">Kursus &amp; Jadwal ({{ $murid->kursus->count() }})</a>
</nav>

@if ($tab === 'profil')
    @include('partials.murid-profil')
@else
    @include('partials.kursus-daftar', ['peran' => 'wali'])
@endif
@endsection