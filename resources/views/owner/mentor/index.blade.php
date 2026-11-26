@extends('layouts.owner')
@section('judul', 'Data Mentor')

@section('isi')
<div class="kepala">
    <div>
        <h1>Data Mentor</h1>
        <p class="sub">Akun dan profil lengkap mentor beserta bidang yang dikuasai.</p>
    </div>
    <div class="kumpulan-tombol">
        <button type="button" class="tombol" id="buka-cetak">Cetak Data</button>
        <a href="{{ route('owner.mentor.create') }}" class="tombol utama">+ Tambah Mentor</a>
    </div>
</div>

<section class="panel">
    <form method="GET" action="{{ route('owner.mentor.index') }}" class="cari">
        <input type="text" name="cari" value="{{ $cari }}" placeholder="Cari nama atau email mentor...">
        <select name="bidang">
            <option value="">Semua bidang</option>
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
        <button type="submit" class="tombol">Cari</button>
        @if ($cari || $bidang || $tingkat)
            <a href="{{ route('owner.mentor.index') }}" class="tombol">Reset</a>
        @endif
    </form>
    @if ($cari || $bidang || $tingkat)
        <p class="sub jarak-bawah">Ditemukan {{ $mentor->count() }} mentor.</p>
    @endif

    <table class="tabel">
        <thead>
            <tr><th>Nama</th><th>Kontak</th><th>Bidang dan tingkat dikuasai</th><th>Kursus aktif</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            @forelse ($mentor as $m)
                <tr>
                    <td>{{ $m->user->nama }}<br><span class="sub">{{ $m->pendidikan_terakhir }} · {{ $m->asal_institusi }}</span></td>
                    <td>{{ $m->user->email }}<br><span class="sub">{{ $m->user->no_hp }}</span></td>
                    <td>
                        @foreach ($m->bidang as $b)
                            <span class="label">{{ $b->nama_bidang }} · {{ implode(', ', $m->tingkatUntuk($b->id)) }}</span>
                        @endforeach
                    </td>
                    <td>{{ $m->kursus_count }}</td>
                    <td class="aksi">
                        <a href="{{ route('owner.mentor.show', $m) }}" class="tombol kecil utama">Detail</a>
                        <a href="{{ route('owner.mentor.edit', $m) }}" class="tombol kecil">Ubah</a>
                        <form method="POST" action="{{ route('owner.mentor.destroy', $m) }}"
                              onsubmit="return confirm('Hapus mentor {{ $m->user->nama }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="tombol kecil bahaya">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="kosong">Data mentor tidak ditemukan.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

{{-- Dialog filter cetak data mentor --}}
@include('partials.dialog-cetak-data', [
    'judulCetak'     => 'Data Mentor',
    'ruteCetak'      => 'owner.mentor.cetak',
    'namaStatus'     => 'status',
    'opsiStatus'     => ['berjalan' => 'Kursus berjalan', 'selesai' => 'Kursus selesai'],
    'statusTerpilih' => ['berjalan', 'selesai'],
    'labelTanpa'     => 'Sertakan mentor yang belum memiliki murid',
])
@endsection
