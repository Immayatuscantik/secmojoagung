@extends('layouts.mentor')
@section('judul', 'Ajukan Honor')

@section('isi')
<div>
    <h1>{{ $pengajuan ? 'Ajukan Ulang Honor' : 'Ajukan Honor' }}</h1>
    <p class="sub">{{ $kursus->murid->nama }} · {{ $kursus->bidang->nama_bidang }} · {{ $kursus->murid->jenjang }}</p>
</div>

@if ($pengajuan)
    <div class="notif gagal">
        Pengajuan sebelumnya dikembalikan oleh owner dengan alasan: <strong>{{ $pengajuan->alasan_pengembalian }}</strong>
    </div>
@endif

<div class="dua-kolom-sama">
    <section class="panel">
        <h2>Rekap pertemuan</h2>
        <dl class="info-lokasi">
            <dt>Paket</dt>            <dd>{{ $kursus->jumlah_pertemuan }} pertemuan</dd>
            <dt>Verifikasi wali</dt>  <dd><span class="label diambil">Disetujui</span> {{ $kursus->tanggal_verifikasi?->translatedFormat('d F Y, H.i') }}</dd>
            <dt>Honor</dt>
            <dd><strong>{{ \App\Support\Rupiah::format($kursus->honor) }}</strong></dd>
        </dl>
        <table class="tabel jarak-atas">
            <thead><tr><th>Ke</th><th>Tanggal</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($kursus->jadwal as $j)
                    <tr>
                        <td>{{ $j->pertemuan_ke }}</td>
                        <td>{{ $j->tanggal->translatedFormat('d F Y') }}</td>
                        <td>
                            <span class="label {{ $j->absensi->status === 'Valid' ? 'diambil' : 'tersedia' }}">{{ $j->absensi->status }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="panel formulir">
        <h2>Link video pembelajaran</h2>
        <p class="bantuan">Unggah video kegiatan belajar ke Google Drive milik Anda, lalu tempel link-nya di sini.</p>

        <ol class="langkah-drive">
            <li>Unggah video ke Google Drive.</li>
            <li>Klik kanan video → <strong>Bagikan</strong> → ubah akses menjadi <strong>"Siapa saja yang memiliki link"</strong>.</li>
            <li>Klik <strong>Salin link</strong>, lalu tempel pada kolom di bawah.</li>
        </ol>

        <form method="POST" action="{{ route('mentor.honor.store', $kursus) }}" id="form-honor">
            @csrf
            <input type="url" name="video" value="{{ old('video') }}"
                   placeholder="https://drive.google.com/file/d/.../view?usp=sharing">
            @error('video') <small class="galat">{{ $message }}</small> @enderror

            <button type="submit" id="kirim" class="tombol utama penuh jarak-atas">Kirim Pengajuan</button>
            <a href="{{ route('mentor.murid.show', $kursus) }}" class="tombol penuh jarak-atas-kecil">Kembali</a>
        </form>
    </section>
</div>
@endsection
