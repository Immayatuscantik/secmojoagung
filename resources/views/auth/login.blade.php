@extends('layouts.tamu')
@section('judul', 'Masuk')
@section('sambutan-judul', 'Sistem Manajemen dan Monitoring Pembelajaran')
@section('sambutan-teks', 'Pantau perkembangan murid, rekam absensi mentor, dan kelola honor mentor dalam satu sistem.')

@section('isi')
<form method="POST" action="{{ route('login.proses') }}" class="flex flex-col">
    @csrf
    <h2 class="text-2xl font-bold">Masuk ke akun Anda</h2>
    <p class="sub mb-7">Untuk owner, admin, mentor, dan wali murid yang sudah terdaftar.</p>

    @if (session('sukses'))
        <div class="notif sukses jarak-bawah">{{ session('sukses') }}</div>
    @endif

    <label for="email" class="mb-1.5 text-sm font-semibold text-tinta">Email</label>
    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" autofocus class="py-3">

    <label for="password" class="mt-4 mb-1.5 text-sm font-semibold text-tinta">Kata sandi</label>
    <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" class="py-3">

    @if ($errors->any())
        <div class="notif gagal mt-4">{{ $errors->first() }}</div>
    @endif

    <button type="submit" class="tombol utama mt-6 py-3 text-[15px]">Masuk</button>
    <p class="mt-4 text-xs text-teks-pudar">Lupa kata sandi? Hubungi owner untuk mengatur ulang akun.</p>

    <div class="mt-8 rounded-xl border border-garis bg-white px-4 py-3.5 text-sm text-teks-sedang">
        Belum punya akun? <a href="{{ route('daftar') }}" class="font-semibold">Daftar sebagai wali murid</a>
    </div>
</form>
@endsection