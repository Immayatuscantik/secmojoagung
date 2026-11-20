@extends('layouts.owner')
@section('judul', 'Detail Pengajuan Honor')

@section('isi')
@php
    $kursus = $pengajuan->kursus;
    $murid  = $kursus->murid;
    $valid  = $kursus->jadwal->filter(fn ($j) => $j->absensi?->status === 'Valid')->count();
    $tinjau = $kursus->jadwal->filter(fn ($j) => $j->absensi?->status === 'Perlu Ditinjau')->count();
    $hadir  = $valid + $tinjau;
@endphp

<div class="kepala">
    <div>
        <h1>Detail Pengajuan Honor</h1>
        <p class="sub">Diajukan {{ $pengajuan->tanggal_pengajuan->translatedFormat('d F Y') }} ·
            <span class="label s-{{ strtolower($pengajuan->status) }}">{{ $pengajuan->status }}</span></p>
    </div>
    <a href="{{ route('owner.honor') }}" class="tombol">Kembali</a>
</div>

<div class="dua-kolom-sama">
    {{-- Kolom kiri: data dan rekap absensi --}}
    <section class="panel">
        <h2>Data pengajuan</h2>
        <dl class="info-lokasi">
            <dt>Mentor</dt>          <dd>{{ $pengajuan->mentor->user->nama }} ({{ $pengajuan->mentor->user->no_hp }})</dd>
            <dt>Murid</dt>           <dd>{{ $murid->nama }} · {{ $murid->jenjang }}</dd>
            <dt>Kursus</dt>          <dd>{{ $kursus->bidang->nama_bidang }} · {{ $kursus->jumlah_pertemuan }} pertemuan</dd>
            <dt>Kehadiran</dt>       <dd>{{ $hadir }}/{{ $kursus->jumlah_pertemuan }} pertemuan ({{ $valid }} Valid, {{ $tinjau }} Perlu Ditinjau)</dd>
            <dt>Verifikasi wali</dt>
            <dd>
                @php [$teksV, $kelasV] = $kursus->labelVerifikasi(); @endphp
                <span class="label {{ $kelasV }}">{{ $teksV }}</span>
                {{ $kursus->verifikator?->nama ?? $murid->wali->user->nama }}, {{ $kursus->tanggal_verifikasi?->translatedFormat('d F Y, H.i') }}
            </dd>
            <dt>Honor</dt>           <dd><strong>{{ \App\Support\Rupiah::format($kursus->honor) }}</strong></dd>
        </dl>

        <h2 class="jarak-atas">Rekap absensi</h2>
        <table class="tabel">
            <thead><tr><th>Ke</th><th>Tanggal</th><th>Jarak</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($kursus->jadwal as $j)
                    <tr>
                        <td>{{ $j->pertemuan_ke }}</td>
                        <td>{{ $j->tanggal->translatedFormat('d M Y') }}</td>
                        @if ($j->absensi)
                            <td>{{ number_format($j->absensi->jarak, 0, ',', '.') }} m</td>
                            <td><span class="label {{ $j->absensi->status === 'Valid' ? 'diambil' : 'tersedia' }}">{{ $j->absensi->status }}</span></td>
                            <td><a href="{{ route('owner.absensi.show', $j->absensi) }}" class="tombol kecil">Detail</a></td>
                        @else
                            <td>-</td>
                            <td><span class="label tidak-hadir">Tidak absen</span></td>
                            <td><span class="sub">{{ $j->keterangan }}</span></td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    {{-- Kolom kanan: video dan tindakan --}}
    <div>
        <section class="panel">
            <h2>Video pembelajaran</h2>
            @if ($embed)
                <iframe src="{{ $embed }}" class="video-drive" allow="autoplay" allowfullscreen></iframe>
            @endif
            <a href="{{ $pengajuan->video }}" target="_blank" rel="noopener" class="tombol kecil jarak-atas">Buka di Google Drive</a>
            <p class="bantuan">Jika video tidak dapat dibuka, kembalikan pengajuan dan minta mentor memperbaiki akses link.</p>
        </section>

        <section class="panel formulir">
            @switch($pengajuan->status)
                @case('Diajukan')
                    <h2>Verifikasi</h2>
                    <form method="POST" action="{{ route('owner.honor.setujui', $pengajuan) }}"
                          onsubmit="return confirm('Setujui pengajuan honor ini?')">
                        @csrf @method('PUT')
                        <button type="submit" class="tombol utama penuh">Setujui</button>
                    </form>

                    <form method="POST" action="{{ route('owner.honor.kembalikan', $pengajuan) }}" class="jarak-atas">
                        @csrf @method('PUT')
                        <label>Alasan pengembalian</label>
                        <textarea name="alasan_pengembalian" rows="3" placeholder="Contoh: link video tidak dapat dibuka">{{ old('alasan_pengembalian') }}</textarea>
                        @error('alasan_pengembalian') <small class="galat">{{ $message }}</small> @enderror
                        <button type="submit" class="tombol penuh jarak-atas-kecil">Kembalikan ke Mentor</button>
                    </form>
                    @break

                @case('Disetujui')
                    <h2>Pencairan honor</h2>
                    <dl class="info-lokasi">
                        <dt>Nominal</dt> <dd><strong>{{ \App\Support\Rupiah::format($kursus->honor) }}</strong></dd>
                    </dl>
                    <p class="bantuan">Nominal dihitung otomatis dari tarif honor saat kursus didaftarkan.</p>
                    <form method="POST" action="{{ route('owner.honor.cairkan', $pengajuan) }}" enctype="multipart/form-data">
                        @csrf
                        <label>Tanggal pencairan</label>
                        <input type="date" name="tanggal_pencairan" value="{{ old('tanggal_pencairan', today()->toDateString()) }}">
                        @error('tanggal_pencairan') <small class="galat">{{ $message }}</small> @enderror

                        <label class="jarak-atas">Bukti transfer (JPG, PNG, atau PDF, maks. 2 MB)</label>
                        <input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf">
                        @error('bukti_transfer') <small class="galat">{{ $message }}</small> @enderror

                        <button type="submit" class="tombol utama penuh jarak-atas">Simpan Pencairan</button>
                    </form>
                    @break

                @case('Dikembalikan')
                    <h2>Dikembalikan</h2>
                    <p>Alasan: <strong>{{ $pengajuan->alasan_pengembalian }}</strong></p>
                    <p class="bantuan">Menunggu mentor mengajukan ulang.</p>
                    @break

                @case('Dicairkan')
                    <h2>Sudah dicairkan</h2>
                    <dl class="info-lokasi">
                        <dt>Tanggal</dt> <dd>{{ $pengajuan->pencairan->tanggal_pencairan->translatedFormat('d F Y') }}</dd>
                        <dt>Nominal</dt> <dd><strong>{{ \App\Support\Rupiah::format($pengajuan->pencairan->nominal) }}</strong></dd>
                    </dl>
                    @if (\Illuminate\Support\Str::endsWith($pengajuan->pencairan->bukti_transfer, '.pdf'))
                        <a href="{{ asset('storage/' . $pengajuan->pencairan->bukti_transfer) }}" target="_blank" class="tombol kecil">Lihat bukti transfer (PDF)</a>
                    @else
                        <img src="{{ asset('storage/' . $pengajuan->pencairan->bukti_transfer) }}" alt="Bukti transfer" class="bukti-transfer">
                    @endif
                    @break
            @endswitch
        </section>
    </div>
</div>
@endsection
