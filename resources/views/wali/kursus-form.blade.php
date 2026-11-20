@extends('layouts.wali')
@section('judul', 'Daftar Kursus')

@section('isi')
<div>
    <h1>Daftar Kursus</h1>
    <p class="sub">Pilih kursus dan jadwal belajar untuk <strong>{{ $murid->nama }}</strong> ({{ $murid->jenjang }}).
        Paket berlaku 4 pekan, lalu pendaftaran akan dikonfirmasi oleh admin.</p>
</div>

<form method="POST" action="{{ route('wali.kursus.store', $murid) }}" class="panel formulir lebar">
    @csrf

    <h2>Kursus dan jadwal belajar</h2>
    <small class="bantuan">Hanya kursus yang tersedia untuk jenjang {{ $murid->jenjang }} yang ditampilkan.</small>
    @include('partials.kursus-form-fields')

    <div class="baris-tombol kanan">
        <a href="{{ route('wali.anak.show', [$murid, 'tab' => 'kursus']) }}" class="tombol">Batal</a>
        <button type="submit" class="tombol utama">Daftarkan Kursus</button>
    </div>
</form>
@endsection