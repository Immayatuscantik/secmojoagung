@extends('layouts.mentor')
@section('judul', 'Daftar Job')

@section('isi')
<div>
    <h1>Daftar Job</h1>
    <p class="sub">
        Kursus yang sesuai dengan bidang dan tingkat Anda
        ({{ $mentor->ringkasanKeahlian() ?: 'belum ada bidang' }}),
        diurutkan dari yang terdekat dengan rumah Anda.
    </p>
</div>

<div class="kartu-grid">
    @forelse ($job as $k)
        <article class="kartu-job">
            <div class="kepala-kartu">
                <span class="label tersedia">Tersedia</span>
                <span class="jarak-job">
                    ± {{ $k->jarak < 1000
                        ? number_format($k->jarak, 0, ',', '.') . ' m'
                        : number_format($k->jarak / 1000, 1, ',', '.') . ' km' }}
                    · ± {{ $k->waktu }} menit
                </span>
            </div>

            <h3>{{ $k->bidang->nama_bidang }} · {{ $k->murid->jenjang }}</h3>

            <dl>
                <dt>Murid</dt>   <dd>{{ $k->murid->nama }}</dd>
                <dt>Hari</dt>    <dd>{{ $k->hariBelajar() }}</dd>
                <dt>Jam</dt>     <dd>{{ $k->jamBelajar() }}</dd>
                <dt>Mulai</dt>   <dd>{{ $k->jadwal->first()?->tanggal->translatedFormat('d F Y') ?? '-' }}</dd>
                <dt>Paket</dt>   <dd>{{ $k->jumlah_pertemuan }} pertemuan</dd>
                <dt>Honor</dt>   <dd><strong>{{ \App\Support\Rupiah::format($k->honor) }}</strong></dd>
                <dt>Alamat</dt>  <dd>{{ $k->murid->alamat }}</dd>
            </dl>

            <a href="https://www.google.com/maps/dir/?api=1&origin={{ $mentor->latitude }},{{ $mentor->longitude }}&destination={{ $k->murid->latitude }},{{ $k->murid->longitude }}"
               target="_blank" rel="noopener" class="tombol penuh">Lihat Rute</a>

            <form method="POST" action="{{ route('mentor.job.ambil', $k) }}"
                  onsubmit="return confirm('Ambil job {{ $k->bidang->nama_bidang }} untuk {{ $k->murid->nama }}?')">
                @csrf
                <button type="submit" class="tombol utama penuh">Ambil Job</button>
            </form>
        </article>
    @empty
        <p class="kosong-besar">Belum ada job yang sesuai dengan bidang Anda.</p>
    @endforelse
</div>

<p class="bantuan">Jarak dihitung dalam garis lurus (rumus Haversine), sedangkan waktu tempuh diperkirakan dengan kecepatan rata-rata sepeda motor 30 km/jam.</p>
@endsection