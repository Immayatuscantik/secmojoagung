@extends('layouts.mentor')
@section('judul', 'Profil Saya')

@section('isi')
<div>
    <h1>Profil Saya</h1>
    <p class="sub">Data profil hanya dapat diubah oleh owner/admin. Hubungi owner jika ada data yang perlu diperbaiki.</p>
</div>

<nav class="tab">
    <a href="{{ route('mentor.profil', ['tab' => 'profil']) }}" class="{{ $tab === 'profil' ? 'aktif' : '' }}">Profil Pribadi</a>
    <a href="{{ route('mentor.profil', ['tab' => 'bidang']) }}" class="{{ $tab === 'bidang' ? 'aktif' : '' }}">Bidang Dikuasai ({{ $mentor->bidang->count() }})</a>
</nav>

@if ($tab === 'profil')
    @include('partials.mentor-profil')
@else
    @include('partials.mentor-bidang')
@endif
@endsection