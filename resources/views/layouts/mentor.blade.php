@extends('layouts.aplikasi')

@section('peran', 'Mentor')

@section('menu')
    <a href="{{ route('mentor.job') }}" class="menu-tautan {{ request()->routeIs('mentor.job*') ? 'aktif' : '' }}">
        <x-ikon nama="job" /> Daftar Job</a>
    <a href="{{ route('mentor.absensi') }}" class="menu-tautan {{ request()->routeIs('mentor.absensi*') ? 'aktif' : '' }}">
        <x-ikon nama="absensi" /> Absensi</a>
    <a href="{{ route('mentor.murid') }}" class="menu-tautan {{ request()->routeIs('mentor.murid*') ? 'aktif' : '' }}">
        <x-ikon nama="murid" /> Murid Saya</a>
    <a href="{{ route('mentor.honor') }}" class="menu-tautan {{ request()->routeIs('mentor.honor*') ? 'aktif' : '' }}">
        <x-ikon nama="honor" /> Status Honor</a>
    <a href="{{ route('mentor.portofolio') }}" class="menu-tautan {{ request()->routeIs('mentor.portofolio') ? 'aktif' : '' }}">
        <x-ikon nama="portofolio" /> Portofolio</a>
    <a href="{{ route('mentor.profil') }}" class="menu-tautan {{ request()->routeIs('mentor.profil') ? 'aktif' : '' }}">
        <x-ikon nama="profil" /> Profil Saya</a>
@endsection