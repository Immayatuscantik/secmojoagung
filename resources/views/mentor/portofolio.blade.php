@extends('layouts.mentor')
@section('judul', 'Portofolio')

@section('isi')
<div class="kepala">
    <div>
        <h1>Portofolio Saya</h1>
        <p class="sub">Rekam jejak mengajar, murid yang pernah diajar, dan honor yang sudah diterima.</p>
    </div>
    <button type="button" class="tombol tanpa-cetak" onclick="window.print()">Cetak</button>
</div>

@include('partials.portofolio-mentor')
@endsection