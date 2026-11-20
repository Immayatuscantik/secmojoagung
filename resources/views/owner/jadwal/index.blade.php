@extends('layouts.owner')
@section('judul', 'Pengajuan Ganti Jadwal')

@section('isi')
<div>
    <h1>Pengajuan Ganti Jadwal</h1>
    <p class="sub">Mentor yang berhalangan mengajukan hari pengganti. Jadwal baru berlaku setelah disetujui. Pastikan jadwal sudah disepakati dengan wali murid.</p>
</div>

<nav class="tab-status">
    @foreach (['Menunggu', 'Disetujui', 'Ditolak'] as $s)
        <a href="{{ route('owner.jadwal.index', ['status' => $s]) }}" class="{{ $status === $s ? 'aktif' : '' }}">
            {{ $s }} ({{ $jumlah[$s] ?? 0 }})
        </a>
    @endforeach
</nav>

<section class="panel">
    <div class="gulir-tabel">
    <table class="tabel">
        <thead>
            <tr><th>Diajukan</th><th>Mentor</th><th>Murid / kursus</th><th>Jadwal semula</th><th>Jadwal diusulkan</th><th>Alasan</th>
                <th>{{ $status === 'Menunggu' ? 'Aksi' : 'Diproses' }}</th></tr>
        </thead>
        <tbody>
            @forelse ($pengajuan as $p)
                @php $j = $p->jadwal; $k = $j->kursus; @endphp
                <tr>
                    <td>{{ $p->created_at->translatedFormat('d M Y, H.i') }}</td>
                    <td>{{ $p->mentor->user->nama }}</td>
                    <td>
                        <a href="{{ route('owner.murid.show', [$k->id_murid, 'tab' => 'kursus']) }}">{{ $k->murid->nama }}</a>
                        <br><span class="sub">{{ $k->bidang->nama_bidang }} · pertemuan ke-{{ $j->pertemuan_ke }}</span>
                        <br><span class="sub">Wali: {{ $k->murid->wali->user->nama }} · {{ $k->murid->wali->user->no_hp }}</span>
                    </td>
                    <td>
                        @if ($p->status === 'Disetujui')
                            {{ ($j->tanggal_asal ?? $j->tanggal)->translatedFormat('D, d M Y') }}
                        @else
                            {{ $j->tanggal->translatedFormat('D, d M Y') }}<br>
                            <span class="sub">{{ substr($j->jam_mulai, 0, 5) }}–{{ substr($j->jam_selesai, 0, 5) }}</span>
                        @endif
                    </td>
                    <td><strong>{{ $p->uraianJadwalBaru() }}</strong></td>
                    <td>{{ $p->alasan }}</td>
                    <td class="aksi">
                        @if ($p->status === 'Menunggu')
                            <form method="POST" action="{{ route('owner.jadwal.setujui', $p) }}"
                                  onsubmit="return confirm('Setujui dan pindahkan pertemuan ke {{ $p->tanggal_baru->translatedFormat('d M Y') }}?')">
                                @csrf @method('PUT')
                                <button type="submit" class="tombol kecil utama">Setujui</button>
                            </form>
                            <button type="button" class="tombol kecil bahaya buka-dialog" data-dialog="tolak-{{ $p->id }}">Tolak</button>
                        @else
                            <span class="label {{ $p->kelasLabel() }}">{{ $p->status }}</span>
                            <br><span class="sub">{{ $p->pemroses?->nama }}, {{ $p->diproses_pada?->translatedFormat('d M Y, H.i') }}</span>
                            @if ($p->catatan_pengelola)<br><span class="sub">{{ $p->catatan_pengelola }}</span>@endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="kosong">Tidak ada pengajuan berstatus {{ strtolower($status) }}.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if ($pengajuan->hasPages())
        <div class="halaman">
            @if ($pengajuan->onFirstPage())
                <span class="tombol kecil nonaktif">&laquo; Sebelumnya</span>
            @else
                <a href="{{ $pengajuan->previousPageUrl() }}" class="tombol kecil">&laquo; Sebelumnya</a>
            @endif
            <span class="sub">Halaman {{ $pengajuan->currentPage() }} dari {{ $pengajuan->lastPage() }}</span>
            @if ($pengajuan->hasMorePages())
                <a href="{{ $pengajuan->nextPageUrl() }}" class="tombol kecil">Berikutnya &raquo;</a>
            @else
                <span class="tombol kecil nonaktif">Berikutnya &raquo;</span>
            @endif
        </div>
    @endif
</section>

{{-- Pop-up alasan penolakan --}}
@foreach ($pengajuan->where('status', 'Menunggu') as $p)
    @php $bukaUlang = (string) old('id_pengajuan_tolak') === (string) $p->id; @endphp
    <dialog id="tolak-{{ $p->id }}" class="dialog {{ $bukaUlang ? 'buka-otomatis' : '' }}">
        <div class="kepala">
            <div>
                <h2>Tolak pengajuan ganti jadwal</h2>
                <p class="sub">{{ $p->mentor->user->nama }} · {{ $p->jadwal->kursus->murid->nama }} · pertemuan ke-{{ $p->jadwal->pertemuan_ke }}</p>
            </div>
            <button type="button" class="tombol kecil tutup-dialog">Tutup</button>
        </div>
        <form method="POST" action="{{ route('owner.jadwal.tolak', $p) }}" class="formulir jarak-atas">
            @csrf @method('PUT')
            <input type="hidden" name="id_pengajuan_tolak" value="{{ $p->id }}">
            <label>Alasan penolakan</label>
            <input type="text" name="catatan_pengelola" maxlength="255" value="{{ $bukaUlang ? old('catatan_pengelola') : '' }}"
                   placeholder="Contoh: wali belum menyetujui tanggal tersebut, silakan sepakati ulang">
            @if ($bukaUlang) @error('catatan_pengelola') <small class="galat">{{ $message }}</small> @enderror @endif
            <div class="baris-tombol">
                <button type="submit" class="tombol bahaya">Tolak Pengajuan</button>
            </div>
        </form>
    </dialog>
@endforeach
@include('partials.dialog-skrip')
@endsection
