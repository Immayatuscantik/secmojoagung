@extends('layouts.owner')
@section('judul', 'Riwayat Aktivitas')

@section('isi')
<div>
    <h1>Riwayat Aktivitas</h1>
    <p class="sub">Catatan tindakan owner dan admin, untuk menelusuri siapa yang melakukan perubahan data.</p>
</div>

<section class="panel">
    <div class="kepala">
        <h2>Daftar aktivitas</h2>
        <form method="GET" action="{{ route('owner.aktivitas') }}" class="filter">
            <select name="pengguna">
                <option value="">Semua pengguna</option>
                @foreach ($pengguna as $p)
                    <option value="{{ $p->id }}" @selected(request('pengguna') == $p->id)>
                        {{ $p->nama }} ({{ ucfirst($p->peran) }})
                    </option>
                @endforeach
            </select>
            <input type="date" name="tanggal" value="{{ request('tanggal') }}">
            <button type="submit" class="tombol kecil utama">Terapkan</button>
            @if (request('pengguna') || request('tanggal'))
                <a href="{{ route('owner.aktivitas') }}" class="tombol kecil">Reset</a>
            @endif
        </form>
    </div>

    <table class="tabel">
        <thead>
            <tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
            @forelse ($log as $l)
                <tr>
                    <td>{{ $l->created_at->translatedFormat('d M Y, H.i') }}</td>
                    <td>
                        @if ($l->user)
                            {{ $l->user->nama }} <span class="label">{{ ucfirst($l->user->peran) }}</span>
                        @else
                            <span class="sub">Akun sudah dihapus</span>
                        @endif
                    </td>
                    <td>{{ $l->aktivitas }}</td>
                    <td>{{ $l->keterangan ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="kosong">Belum ada aktivitas.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($log->hasPages())
        <div class="halaman">
            @if ($log->onFirstPage())
                <span class="tombol kecil nonaktif">&laquo; Sebelumnya</span>
            @else
                <a href="{{ $log->previousPageUrl() }}" class="tombol kecil">&laquo; Sebelumnya</a>
            @endif

            <span class="sub">Halaman {{ $log->currentPage() }} dari {{ $log->lastPage() }}</span>

            @if ($log->hasMorePages())
                <a href="{{ $log->nextPageUrl() }}" class="tombol kecil">Berikutnya &raquo;</a>
            @else
                <span class="tombol kecil nonaktif">Berikutnya &raquo;</span>
            @endif
        </div>
    @endif
</section>
@endsection