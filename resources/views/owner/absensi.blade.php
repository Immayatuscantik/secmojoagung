@extends('layouts.owner')
@section('judul', 'Detail Absensi')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

@section('isi')
@php
    $jadwal = $absensi->jadwal;
    $kursus = $jadwal->kursus;
    $murid  = $kursus->murid;
@endphp

<div class="kepala">
    <div>
        <h1>Detail Absensi</h1>
        <p class="sub">{{ $murid->nama }} · {{ $kursus->bidang->nama_bidang }} · pertemuan {{ $jadwal->labelKe() }}</p>
    </div>
    <a href="{{ url()->previous() }}" class="tombol">Kembali</a>
</div>

<div class="dua-kolom-sama">
    <section class="panel">
        <h2>Swafoto</h2>
        <img src="{{ asset('storage/' . $absensi->foto) }}" alt="Swafoto mentor" class="kamera">

        <dl class="info-lokasi">
            <dt>Mentor</dt>          <dd>{{ $kursus->mentor?->user->nama ?? '-' }}</dd>
            <dt>Jadwal</dt>          <dd>{{ $jadwal->tanggal->translatedFormat('l, d F Y') }}, {{ substr($jadwal->jam_mulai, 0, 5) }}–{{ substr($jadwal->jam_selesai, 0, 5) }}</dd>
            <dt>Waktu absen</dt>     <dd>{{ $absensi->waktu_absen->translatedFormat('d F Y, H.i') }} WIB</dd>
            <dt>Koordinat absen</dt> <dd>{{ $absensi->latitude }}, {{ $absensi->longitude }}</dd>
            <dt>Jarak</dt>           <dd>{{ number_format($absensi->jarak, 2, ',', '.') }} meter (Valid ≤ {{ $radius }} m, Perlu Ditinjau ≤ {{ $radiusMaks }} m)</dd>
            <dt>Status</dt>
            <dd><span class="label {{ $absensi->status === 'Valid' ? 'diambil' : 'tersedia' }}">{{ $absensi->status }}</span></dd>
            <dt>Catatan materi</dt>  <dd>{{ $absensi->catatan_materi }}</dd>
        </dl>

        <h2 class="jarak-atas">Riwayat perubahan catatan</h2>
        @forelse ($absensi->riwayatCatatan as $r)
            <div class="riwayat-catatan">
                <span class="sub">Diubah mentor {{ $r->created_at->translatedFormat('d F Y, H.i') }} WIB</span>
                <p><s>{{ $r->catatan_lama }}</s></p>
                <p>→ {{ $r->catatan_baru }}</p>
            </div>
        @empty
            <p class="bantuan">Catatan belum pernah diubah.</p>
        @endforelse
    </section>

    <section class="panel">
        <h2>Peta lokasi</h2>
        <p class="sub">Penanda biru: rumah murid (lingkaran dalam = {{ $radius }} m, lingkaran luar = {{ $radiusMaks }} m). Titik merah: lokasi mentor saat absen.</p>
        <div id="peta" class="peta"
             data-lat-rumah="{{ $murid->latitude }}" data-lng-rumah="{{ $murid->longitude }}"
             data-lat-absen="{{ $absensi->latitude }}" data-lng-absen="{{ $absensi->longitude }}"
             data-radius="{{ $radius }}" data-radius-maks="{{ $radiusMaks }}"></div>
    </section>
</div>
@endsection

@push('script')
<script>
    const el     = document.getElementById('peta');
    const rumah  = [parseFloat(el.dataset.latRumah), parseFloat(el.dataset.lngRumah)];
    const absen  = [parseFloat(el.dataset.latAbsen), parseFloat(el.dataset.lngAbsen)];
    const radius = parseFloat(el.dataset.radius);

    const peta = L.map('peta');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(peta);

    L.circle(rumah, { radius: parseFloat(el.dataset.radiusMaks), color: '#e0a46e', fillOpacity: 0.08, dashArray: '6 6' }).addTo(peta);
    L.circle(rumah, { radius: radius, color: '#2c6db5', fillOpacity: 0.1 }).addTo(peta);
    L.marker(rumah).addTo(peta).bindPopup('Rumah murid');
    L.circleMarker(absen, { radius: 8, color: '#c0392b', fillColor: '#c0392b', fillOpacity: 0.9 })
        .addTo(peta).bindPopup('Lokasi absensi mentor');
    L.polyline([rumah, absen], { color: '#555', dashArray: '4 6' }).addTo(peta);

    peta.fitBounds(L.latLngBounds([rumah, absen]).pad(0.5), { maxZoom: 17 });
</script>
@endpush