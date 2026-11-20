@extends('layouts.mentor')
@section('judul', 'Murid Saya')

@section('isi')
<div>
    <h1>Murid Saya</h1>
    <p class="sub">Kursus yang Anda ajar beserta progres pertemuan setiap paket.</p>
</div>

@forelse ($kursus as $k)
    <section class="panel">
        <div class="kepala">
            <div>
                <h2>{{ $k->murid->nama }}
                    @if ($k->diskusiTerbuka() && $k->tanggapan->last()?->id_user !== auth()->id())
                        <span class="label tidak-hadir">Perlu tanggapan</span>
                    @endif
                </h2>
                <p class="sub">{{ $k->bidang->nama_bidang }} · {{ $k->murid->jenjang }} · {{ $k->hariBelajar() }}, {{ $k->jamBelajar() }}</p>
                <p class="sub">{{ $k->murid->alamat }}</p>
            </div>
            <div class="baris-tombol tanpa-jarak">
                <strong>{{ $k->hadir_count }}/{{ $k->jumlah_pertemuan }} pertemuan</strong>
                <a href="{{ route('mentor.murid.show', $k) }}" class="tombol kecil utama">Detail</a>
            </div>
        </div>

        <progress class="progres" value="{{ $k->hadir_count }}" max="{{ $k->jumlah_pertemuan }}"></progress>
        @include('partials.status-honor-mentor')
    </section>
@empty
    <p class="kosong-besar">Anda belum memiliki murid. Ambil job pada menu Daftar Job.</p>
@endforelse
@endsection
