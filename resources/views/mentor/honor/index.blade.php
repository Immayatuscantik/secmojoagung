@extends('layouts.mentor')
@section('judul', 'Status Honor')

@section('isi')
<div>
    <h1>Status Honor</h1>
    <p class="sub">Riwayat pengajuan honor beserta status verifikasi dan pencairannya.</p>
</div>

<section class="panel">
    <table class="tabel">
        <thead>
            <tr><th>Tanggal pengajuan</th><th>Murid</th><th>Kursus</th><th>Honor</th><th>Status</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
            @forelse ($pengajuan as $p)
                <tr>
                    <td>{{ $p->tanggal_pengajuan->translatedFormat('d F Y') }}</td>
                    <td>
                        {{ $p->kursus->murid->nama }}<br>
                        <a href="{{ $p->video }}" target="_blank" rel="noopener" class="sub">Lihat video</a>
                    </td>
                    <td>{{ $p->kursus->bidang->nama_bidang }}<br><span class="sub">{{ $p->kursus->jumlah_pertemuan }} pertemuan</span></td>
                    <td>{{ \App\Support\Rupiah::format($p->pencairan?->nominal ?? $p->kursus->honor) }}</td>
                    <td><span class="label s-{{ strtolower($p->status) }}">{{ $p->status }}</span></td>
                    <td>
                        @switch($p->status)
                            @case('Diajukan')
                                <span class="sub">Menunggu verifikasi owner.</span>
                                @break
                            @case('Dikembalikan')
                                {{ $p->alasan_pengembalian }}
                                <a href="{{ route('mentor.honor.form', $p->kursus) }}" class="tombol kecil utama">Ajukan Ulang</a>
                                @break
                            @case('Disetujui')
                                <span class="sub">Disetujui, menunggu pencairan.</span>
                                @break
                            @case('Dicairkan')
                                Dicairkan {{ $p->pencairan?->tanggal_pencairan?->translatedFormat('d F Y') }}
                                @if ($p->pencairan)
                                    <a href="{{ asset('storage/' . $p->pencairan->bukti_transfer) }}" target="_blank" class="tombol kecil">Bukti transfer</a>
                                @endif
                                @break
                        @endswitch
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="kosong">Belum ada pengajuan honor.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
