@extends('layouts.owner')
@section('judul', 'Tambah Kursus')

@section('isi')
<div>
    <h1>Tambah Kursus</h1>
    <p class="sub">Kursus baru untuk <strong>{{ $murid->nama }}</strong> ({{ $murid->jenjang }}).
        Kursus yang ditambahkan admin langsung tampil di Daftar Job mentor.</p>
</div>

<form method="POST" action="{{ route('owner.kursus.store', $murid) }}" class="panel formulir lebar">
    @csrf

    <h2>Kursus dan jadwal belajar</h2>
    <small class="bantuan">Hanya kursus yang tersedia untuk jenjang {{ $murid->jenjang }} yang ditampilkan.</small>
    @include('partials.kursus-form-fields')

    <div class="baris-tombol kanan">
        <a href="{{ route('owner.murid.show', [$murid, 'tab' => 'kursus']) }}" class="tombol">Batal</a>
        <button type="submit" class="tombol utama">Simpan Kursus</button>
    </div>
</form>
@endsection