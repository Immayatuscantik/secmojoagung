@extends('layouts.aplikasi')

@section('peran', 'Wali murid')

@section('menu')
    <a href="{{ route('wali.anak') }}" class="menu-tautan {{ request()->routeIs('wali.anak*') ? 'aktif' : '' }}">
        <x-ikon nama="anak" /> Anak Saya</a>
@endsection