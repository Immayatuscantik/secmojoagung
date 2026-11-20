{{-- Data pribadi mentor; dipakai di Detail Mentor (owner/admin) dan Profil Saya (mentor) --}}
@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

<div class="dua-kolom-sama">
    <section class="panel">
        <h2>Data pribadi</h2>
        <dl class="info-lokasi">
            <dt>Nama</dt>                <dd>{{ $mentor->user->nama }}</dd>
            <dt>Jenis kelamin</dt>       <dd>{{ $mentor->jenis_kelamin }}</dd>
            <dt>Tanggal lahir</dt>       <dd>{{ $mentor->tanggal_lahir->translatedFormat('d F Y') }} ({{ $mentor->umur() }} tahun)</dd>
            <dt>Pendidikan terakhir</dt> <dd>{{ $mentor->pendidikan_terakhir }}</dd>
            <dt>Asal institusi</dt>      <dd>{{ $mentor->asal_institusi }}</dd>
            <dt>Pengalaman mengajar</dt> <dd>{{ $mentor->pengalaman_mengajar ?: '-' }}</dd>
        </dl>

        <h2 class="jarak-atas">Akun dan kontak</h2>
        <dl class="info-lokasi">
            <dt>Email</dt>  <dd>{{ $mentor->user->email }}</dd>
            <dt>No. HP</dt> <dd>{{ $mentor->user->no_hp }}</dd>
            <dt>Terdaftar sejak</dt> <dd>{{ $mentor->created_at->translatedFormat('d F Y') }}</dd>
        </dl>
    </section>

    <section class="panel">
        <h2>Alamat rumah</h2>
        <p>{{ $mentor->alamat }}</p>
        <div id="peta-mentor" class="peta"
             data-lat="{{ $mentor->latitude }}" data-lng="{{ $mentor->longitude }}"></div>
    </section>
</div>

@push('script')
<script>
    (function () {
        const el = document.getElementById('peta-mentor');
        const titik = [parseFloat(el.dataset.lat), parseFloat(el.dataset.lng)];
        const peta = L.map(el).setView(titik, 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; OpenStreetMap'
        }).addTo(peta);
        L.marker(titik).addTo(peta).bindPopup('Rumah mentor');
    })();
</script>
@endpush