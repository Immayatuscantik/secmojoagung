@extends('layouts.wali')
@section('judul', 'Anak Saya')

@section('isi')
<div class="kepala">
    <div>
        <h1>Anak Saya</h1>
        <p class="sub">Daftar anak yang Anda daftarkan beserta kursus yang diikuti.</p>
    </div>
    <a href="{{ route('wali.anak.create') }}" class="tombol utama">+ Tambah Anak</a>
</div>

@forelse ($anak as $m)
    <section class="panel">
        <div class="kepala">
            <div>
                <h2>{{ $m->nama }}</h2>
                <p class="sub">{{ $m->jenjang }}{{ $m->asal_sekolah ? ' · ' . $m->asal_sekolah : '' }} · {{ $m->umur() }} tahun</p>
            </div>
            <div class="baris-tombol tanpa-jarak">
                <a href="{{ route('wali.anak.show', [$m, 'tab' => 'kursus']) }}" class="tombol kecil utama">Detail</a>
                <a href="{{ route('wali.kursus.create', $m) }}" class="tombol kecil">+ Daftar Kursus</a>
            </div>
        </div>

        <table class="tabel jarak-atas">
            <thead>
                <tr><th>Kursus</th><th>Mentor</th><th>Status</th><th>Progres</th></tr>
            </thead>
            <tbody>
                @forelse ($m->kursus as $k)
                    @php [$teks, $kelas] = $k->labelStatus(); @endphp
                    <tr>
                        <td>{{ $k->bidang->nama_bidang }}</td>
                        <td>{{ $k->mentor?->user->nama ?? '-' }}</td>
                        <td>
                            <span class="label {{ $kelas }}">{{ $teks }}</span>
                            @if ($k->status === 'diambil' && $k->pertemuanLengkap() && $k->verifikasi_wali !== 'disetujui')
                                <a href="{{ route('wali.anak.show', [$m, 'tab' => 'kursus']) }}" class="label tidak-hadir">Perlu verifikasi Anda</a>
                            @endif
                        </td>
                        <td>{{ $k->hadir_count }} / {{ $k->jumlah_pertemuan }} pertemuan</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="kosong">Belum mengambil kursus.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@empty
    <section class="panel">
        <p class="kosong-besar">Anda belum mendaftarkan anak. Klik <strong>+ Tambah Anak</strong> untuk memulai.</p>
    </section>
@endforelse
@endsection
