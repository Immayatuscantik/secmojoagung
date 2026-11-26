@extends('layouts.owner')
@section('judul', 'Data Murid dan Kursus')

@section('isi')
<div class="kepala">
    <div>
        <h1>Data Murid dan Kursus</h1>
        <p class="sub">Murid didaftarkan oleh wali murid atau oleh admin. Kursus dari wali perlu dikonfirmasi sebelum tampil di Daftar Job mentor.</p>
    </div>
    <div class="kumpulan-tombol">
        <button type="button" class="tombol" id="buka-cetak">Cetak Data</button>
        <a href="{{ route('owner.murid.create') }}" class="tombol utama">+ Tambah Murid</a>
    </div>
</div>

<section class="panel">
    <form method="GET" action="{{ route('owner.murid.index') }}" class="cari">
        <input type="text" name="cari" value="{{ $cari }}" placeholder="Cari nama murid...">
        <select name="bidang">
            <option value="">Semua kursus</option>
            @foreach ($daftarBidang as $b)
                <option value="{{ $b->id }}" @selected($bidang === $b->id)>{{ $b->nama_bidang }}</option>
            @endforeach
        </select>
        <select name="tingkat">
            <option value="">Semua tingkat</option>
            @foreach ($daftarTingkat as $t)
                <option value="{{ $t }}" @selected($tingkat === $t)>{{ $t }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">Semua status kursus</option>
            <option value="menunggu" @selected($status === 'menunggu')>Menunggu konfirmasi</option>
            <option value="tersedia" @selected($status === 'tersedia')>Mencari mentor</option>
            <option value="diambil" @selected($status === 'diambil')>Berjalan</option>
        </select>
        <button type="submit" class="tombol">Cari</button>
        @if ($cari || $bidang || $tingkat || $status)
            <a href="{{ route('owner.murid.index') }}" class="tombol">Reset</a>
        @endif
    </form>
    @if ($cari || $bidang || $tingkat || $status)
        <p class="sub jarak-bawah">Ditemukan {{ $murid->count() }} murid.</p>
    @endif

    <table class="tabel">
        <thead>
            <tr><th>Nama</th><th>Wali</th><th>Kursus</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            @forelse ($murid as $m)
                <tr>
                    <td>{{ $m->nama }}<br><span class="sub">{{ $m->jenjang }}</span></td>
                    <td>{{ $m->wali->user->nama }}<br><span class="sub">{{ $m->wali->user->no_hp }}</span></td>
                    <td>
                        @forelse ($m->kursus as $k)
                            @php [$teks, $kelas] = $k->labelStatus(); @endphp
                            <span class="label {{ $kelas }}" title="{{ $teks }}">{{ $k->bidang->nama_bidang }} · {{ $teks }}</span>
                        @empty
                            <span class="sub">Belum ada kursus</span>
                        @endforelse
                    </td>
                    <td class="aksi">
                        <a href="{{ route('owner.murid.show', [$m, 'tab' => 'kursus']) }}" class="tombol kecil utama">Detail</a>
                        <a href="{{ route('owner.murid.edit', $m) }}" class="tombol kecil">Ubah</a>
                        <form method="POST" action="{{ route('owner.murid.destroy', $m) }}"
                              onsubmit="return confirm('Hapus data murid {{ $m->nama }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="tombol kecil bahaya">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="kosong">Data murid tidak ditemukan.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

{{-- Dialog filter cetak data murid --}}
@include('partials.dialog-cetak-data', [
    'judulCetak'     => 'Data Murid',
    'ruteCetak'      => 'owner.murid.cetak',
    'namaStatus'     => 'status_kursus',
    'opsiStatus'     => ['menunggu' => 'Menunggu konfirmasi', 'tersedia' => 'Mencari mentor', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai'],
    'statusTerpilih' => match ($status) {
        'menunggu' => ['menunggu'],
        'tersedia' => ['tersedia'],
        'diambil'  => ['berjalan', 'selesai'],
        default    => ['menunggu', 'tersedia', 'berjalan', 'selesai'],
    },
    'labelTanpa'     => 'Sertakan murid yang belum mengambil kursus',
])
@endsection
