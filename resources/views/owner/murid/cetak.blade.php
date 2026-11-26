{{-- Cetak Data Murid: nama murid, alamat, kursus yang diambil, dan mentor --}}
@extends('layouts.cetak')
@section('judul', 'Data Murid')
@section('judul-cetak', 'DATA MURID BIMBEL PRIVAT SEC MOJOAGUNG')

@section('isi')
<table class="tabel-formal">
    <colgroup><col style="width: 6%"><col style="width: 18%"><col style="width: 22%"><col style="width: 21%"><col style="width: 19%"><col style="width: 14%"></colgroup>
    <thead>
        <tr><th class="no">No</th><th>Nama Murid</th><th>Alamat</th><th>Kursus yang Diambil</th><th>Nama Mentor</th><th>Status</th></tr>
    </thead>
    @forelse ($baris as $b)
        @php $m = $b->murid; @endphp
        <tbody>
            @forelse ($b->kursus as $k)
                <tr>
                    @if ($loop->first)
                        <td class="no" rowspan="{{ $b->kursus->count() }}">{{ $loop->parent->iteration }}</td>
                        <td rowspan="{{ $b->kursus->count() }}">{{ $m->nama }}<br>{{ $m->jenjang }}</td>
                        <td rowspan="{{ $b->kursus->count() }}">{{ $m->alamat }}</td>
                    @endif
                    <td>{{ $k->bidang->nama_bidang }} ({{ $k->jumlah_pertemuan }} pertemuan)</td>
                    <td>{{ $k->mentor?->user->nama ?? 'Belum ada mentor' }}</td>
                    <td>{{ $k->labelStatus()[0] }}</td>
                </tr>
            @empty
                <tr>
                    <td class="no">{{ $loop->iteration }}</td>
                    <td>{{ $m->nama }}<br>{{ $m->jenjang }}</td>
                    <td>{{ $m->alamat }}</td>
                    <td colspan="3" class="kosong">Belum mengambil kursus</td>
                </tr>
            @endforelse
        </tbody>
    @empty
        <tbody><tr><td colspan="6" class="kosong">Tidak ada data murid yang sesuai.</td></tr></tbody>
    @endforelse
</table>
<p class="keterangan-jumlah">Jumlah: {{ $baris->count() }} murid, {{ $baris->sum(fn ($b) => $b->kursus->count()) }} kursus.</p>
@endsection
