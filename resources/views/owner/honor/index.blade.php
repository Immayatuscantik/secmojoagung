@extends('layouts.owner')
@section('judul', 'Pengajuan Honor')

@section('isi')
<div>
    <h1>Pengajuan Honor</h1>
    <p class="sub">Verifikasi pengajuan honor mentor dan lakukan pencairan. Pengajuan hanya dapat dibuat setelah wali murid menyetujui.</p>
</div>

<nav class="tab-status">
    <a href="{{ route('owner.honor') }}" class="{{ $status ? '' : 'aktif' }}">Semua ({{ $jumlah->sum() }})</a>
    @foreach (\App\Http\Controllers\VerifikasiHonorController::STATUS as $s)
        <a href="{{ route('owner.honor', ['status' => $s]) }}" class="{{ $status === $s ? 'aktif' : '' }}">
            {{ $s }} ({{ $jumlah[$s] ?? 0 }})
        </a>
    @endforeach
</nav>

<section class="panel">
    <table class="tabel">
        <thead>
            <tr><th>Tanggal pengajuan</th><th>Mentor</th><th>Murid</th><th>Kursus</th><th>Honor</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            @forelse ($pengajuan as $p)
                <tr>
                    <td>{{ $p->tanggal_pengajuan->translatedFormat('d F Y') }}</td>
                    <td>{{ $p->mentor->user->nama }}</td>
                    <td>{{ $p->kursus->murid->nama }}</td>
                    <td>{{ $p->kursus->bidang->nama_bidang }}<br><span class="sub">{{ $p->kursus->jumlah_pertemuan }} pertemuan</span></td>
                    <td>{{ \App\Support\Rupiah::format($p->pencairan?->nominal ?? $p->kursus->honor) }}</td>
                    <td><span class="label s-{{ strtolower($p->status) }}">{{ $p->status }}</span></td>
                    <td>
                        <a href="{{ route('owner.honor.show', $p) }}" class="tombol kecil {{ in_array($p->status, ['Diajukan', 'Disetujui']) ? 'utama' : '' }}">
                            {{ $p->status === 'Diajukan' ? 'Verifikasi' : ($p->status === 'Disetujui' ? 'Cairkan' : 'Detail') }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="kosong">Belum ada pengajuan honor.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
