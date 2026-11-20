@extends('layouts.wali')
@section('judul', $murid ? 'Ubah Profil Anak' : 'Tambah Anak')

@section('isi')
<div>
    <h1>{{ $murid ? 'Ubah Profil Anak' : 'Tambah Anak' }}</h1>
    <p class="sub">{{ $murid ? 'Perbarui data pribadi anak Anda.' : 'Langkah 1 dari 2: isi data pribadi anak. Setelah disimpan, Anda akan diarahkan untuk memilih kursus.' }}</p>
</div>

<form method="POST" class="panel formulir lebar"
      action="{{ $murid ? route('wali.anak.update', $murid) : route('wali.anak.store') }}">
    @csrf
    @if ($murid) @method('PUT') @endif

    @include('partials.murid-form')

    <div class="baris-tombol kanan">
        <a href="{{ $murid ? route('wali.anak.show', $murid) : route('wali.anak') }}" class="tombol">Batal</a>
        <button type="submit" class="tombol utama">{{ $murid ? 'Simpan Perubahan' : 'Simpan dan Lanjut Pilih Kursus' }}</button>
    </div>
</form>
@endsection