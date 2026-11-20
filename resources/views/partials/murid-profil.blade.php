{{-- Profil pribadi murid; dipakai di Detail Murid (owner/admin) dan Detail Anak (wali) --}}
@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

<div class="dua-kolom-sama">
    <section class="panel">
        <h2>Data pribadi</h2>
        <dl class="info-lokasi">
            <dt>Nama</dt>            <dd>{{ $murid->nama }}</dd>
            <dt>Jenis kelamin</dt>   <dd>{{ $murid->jenis_kelamin }}</dd>
            <dt>Tanggal lahir</dt>   <dd>{{ $murid->tanggal_lahir->translatedFormat('d F Y') }} ({{ $murid->umur() }} tahun)</dd>
            <dt>Jenjang</dt>         <dd>{{ $murid->jenjang }}</dd>
            <dt>Asal sekolah</dt>    <dd>{{ $murid->asal_sekolah ?: '-' }}</dd>
            <dt>Catatan khusus</dt>  <dd>{{ $murid->catatan_khusus ?: '-' }}</dd>
        </dl>

        @if ($tampilWali ?? false)
            <h2 class="jarak-atas">Data wali</h2>
            <dl class="info-lokasi">
                <dt>Nama wali</dt> <dd>{{ $murid->wali->user->nama }} ({{ $murid->wali->hubungan }})</dd>
                <dt>No. HP</dt>    <dd>{{ $murid->wali->user->no_hp }}</dd>
                <dt>Email</dt>     <dd>{{ $murid->wali->user->email }}</dd>
                <dt>Pekerjaan</dt> <dd>{{ $murid->wali->pekerjaan ?: '-' }}</dd>
            </dl>
        @endif
    </section>

    <section class="panel">
        <h2>Alamat tempat belajar</h2>
        <p>{{ $murid->alamat }}</p>
        <div id="peta-profil-murid" class="peta"
             data-lat="{{ $murid->latitude }}" data-lng="{{ $murid->longitude }}"></div>
    </section>
</div>

@push('script')
<script>
    (function () {
        const el = document.getElementById('peta-profil-murid');
        const titik = [parseFloat(el.dataset.lat), parseFloat(el.dataset.lng)];
        const peta = L.map(el).setView(titik, 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; OpenStreetMap'
        }).addTo(peta);
        L.marker(titik).addTo(peta).bindPopup('Rumah murid');
    })();
</script>
@endpush