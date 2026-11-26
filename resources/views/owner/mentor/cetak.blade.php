{{-- Cetak Data Mentor: nama mentor, alamat, kursus, dan murid yang diajar --}}
@extends('layouts.cetak')
@section('judul', 'Data Mentor')
@section('judul-cetak', 'DATA MENTOR BIMBEL PRIVAT SEC MOJOAGUNG')

@section('isi')
<table class="tabel-formal">
    <colgroup><col style="width: 6%"><col style="width: 18%"><col style="width: 22%"><col style="width: 21%"><col style="width: 19%"><col style="width: 14%"></colgroup>
    <thead>
        <tr><th class="no">No</th><th>Nama Mentor</th><th>Alamat</th><th>Kursus</th><th>Murid yang Diajar</th><th>Status</th></tr>
    </thead>
    @forelse ($baris as $b)
        @php $m = $b->mentor; @endphp
        <tbody>
            @forelse ($b->kursus as $k)
                <tr>
                    @if ($loop->first)
                        <td class="no" rowspan="{{ $b->kursus->count() }}">{{ $loop->parent->iteration }}</td>
                        <td rowspan="{{ $b->kursus->count() }}">{{ $m->user->nama }}<br>{{ $m->user->no_hp }}</td>
                        <td rowspan="{{ $b->kursus->count() }}">{{ $m->alamat }}</td>
                    @endif
                    <td>{{ $k->bidang->nama_bidang }} ({{ $k->jumlah_pertemuan }} pertemuan)</td>
                    <td>{{ $k->murid->nama }} ({{ $k->murid->jenjang }})</td>
                    <td>{{ $k->labelStatus()[0] }}</td>
                </tr>
            @empty
                <tr>
                    <td class="no">{{ $loop->iteration }}</td>
                    <td>{{ $m->user->nama }}<br>{{ $m->user->no_hp }}</td>
                    <td>{{ $m->alamat }}</td>
                    <td colspan="3" class="kosong">Belum memiliki murid</td>
                </tr>
            @endforelse
        </tbody>
    @empty
        <tbody><tr><td colspan="6" class="kosong">Tidak ada data mentor yang sesuai.</td></tr></tbody>
    @endforelse
</table>
<p class="keterangan-jumlah">Jumlah: {{ $baris->count() }} mentor, {{ $baris->sum(fn ($b) => $b->kursus->count()) }} kursus.</p>
@endsection
