@extends('layouts.aplikasi')

@section('peran', auth()->user()->isOwner() ? 'Owner' : 'Admin')

@section('menu')
    <a href="{{ route('owner.dashboard') }}" class="menu-tautan {{ request()->routeIs('owner.dashboard', 'owner.absensi.*') ? 'aktif' : '' }}">
        <x-ikon nama="beranda" /> Dashboard</a>
    <a href="{{ route('owner.mentor.index') }}" class="menu-tautan {{ request()->routeIs('owner.mentor.*') ? 'aktif' : '' }}">
        <x-ikon nama="mentor" /> Data Mentor</a>
    <a href="{{ route('owner.murid.index') }}" class="menu-tautan {{ request()->routeIs('owner.murid.*') ? 'aktif' : '' }}">
        <x-ikon nama="murid" /> Data Murid dan Kursus</a>
    <a href="{{ route('owner.bidang.index') }}" class="menu-tautan {{ request()->routeIs('owner.bidang.*') ? 'aktif' : '' }}">
        <x-ikon nama="bidang" /> Data Bidang</a>
    <a href="{{ route('owner.tarif.index') }}" class="menu-tautan {{ request()->routeIs('owner.tarif.*') ? 'aktif' : '' }}">
        <x-ikon nama="tarif" /> Tarif Honor</a>
    @php $gantiMenunggu = \App\Models\PengajuanJadwal::where('status', 'Menunggu')->count(); @endphp
    <a href="{{ route('owner.jadwal.index') }}" class="menu-tautan {{ request()->routeIs('owner.jadwal.index') ? 'aktif' : '' }}">
        <x-ikon nama="kalender" /> Pengajuan Ganti Jadwal
        @if ($gantiMenunggu) <span class="jumlah-menu">{{ $gantiMenunggu }}</span> @endif</a>
    <a href="{{ route('owner.honor') }}" class="menu-tautan {{ request()->routeIs('owner.honor*') ? 'aktif' : '' }}">
        <x-ikon nama="honor" /> Pengajuan Honor</a>
    <a href="{{ route('owner.laporan.ringkas') }}" class="menu-tautan {{ request()->routeIs('owner.laporan*') ? 'aktif' : '' }}">
        <x-ikon nama="laporan" /> Laporan</a>
    <div class="sub-menu">
        <a href="{{ route('owner.laporan.ringkas') }}" class="{{ request()->routeIs('owner.laporan.ringkas') ? 'aktif' : '' }}">Laporan Ringkas</a>
        <a href="{{ route('owner.laporan') }}" class="{{ request()->routeIs('owner.laporan') ? 'aktif' : '' }}">Laporan Detail</a>
    </div>

    @if (auth()->user()->isOwner())
        <span class="judul-menu">Khusus owner</span>
        <a href="{{ route('owner.admin.index') }}" class="menu-tautan {{ request()->routeIs('owner.admin.*') ? 'aktif' : '' }}">
            <x-ikon nama="admin" /> Kelola Admin</a>
        <a href="{{ route('owner.aktivitas') }}" class="menu-tautan {{ request()->routeIs('owner.aktivitas') ? 'aktif' : '' }}">
            <x-ikon nama="aktivitas" /> Riwayat Aktivitas</a>
    @endif
@endsection
