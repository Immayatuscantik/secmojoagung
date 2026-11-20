@extends('layouts.owner')
@section('judul', 'Ubah Data Murid')

@section('isi')
<div>
    <h1>Ubah Data Murid</h1>
    <p class="sub">Perbaikan data pribadi murid. Perubahan tercatat di Riwayat Aktivitas.</p>
</div>

<form method="POST" action="{{ route('owner.murid.update', $murid) }}" class="panel formulir lebar">
    @csrf
    @method('PUT')

    @include('partials.murid-form')

    <div class="baris-tombol kanan">
        <a href="{{ route('owner.murid.show', $murid) }}" class="tombol">Batal</a>
        <button type="submit" class="tombol utama">Simpan Perubahan</button>
    </div>
</form>
@endsection