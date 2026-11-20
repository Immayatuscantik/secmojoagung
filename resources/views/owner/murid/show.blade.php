@extends('layouts.owner')
@section('judul', 'Detail Murid')

@section('isi')
<div class="kepala">
    <div>
        <h1>{{ $murid->nama }}</h1>
        <p class="sub">{{ $murid->jenjang }} · Wali: {{ $murid->wali->user->nama }}</p>
    </div>
    <div class="baris-tombol tanpa-jarak">
        <a href="{{ route('owner.murid.index') }}" class="tombol">Kembali</a>
        <a href="{{ route('owner.murid.edit', $murid) }}" class="tombol">Ubah Profil</a>
        <a href="{{ route('owner.kursus.create', $murid) }}" class="tombol utama">+ Tambah Kursus</a>
    </div>
</div>

<nav class="tab">
    <a href="{{ route('owner.murid.show', [$murid, 'tab' => 'profil']) }}" class="{{ $tab === 'profil' ? 'aktif' : '' }}">Profil Pribadi</a>
    <a href="{{ route('owner.murid.show', [$murid, 'tab' => 'kursus']) }}" class="{{ $tab === 'kursus' ? 'aktif' : '' }}">Kursus &amp; Jadwal ({{ $murid->kursus->count() }})</a>
</nav>

@if ($tab === 'profil')
    @include('partials.murid-profil', ['tampilWali' => true])
@else
    @include('partials.kursus-daftar', ['peran' => 'pengelola'])
@endif
@endsection