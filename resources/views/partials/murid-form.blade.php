{{-- Isian profil pribadi murid; dipakai wali (tambah/ubah anak) dan owner/admin (ubah murid) --}}
@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/cari-alamat.js') }}"></script>
@endpush

<h3 class="subjudul-form">Data pribadi</h3>
<div class="grid-2">
    <div class="isian">
        <label>Nama lengkap</label>
        <input type="text" name="nama" value="{{ old('nama', $murid?->nama) }}" maxlength="50">
        @error('nama') <small class="galat">{{ $message }}</small> @enderror
    </div>
    <div class="isian">
        <label>Jenis kelamin</label>
        <select name="jenis_kelamin">
            <option value="">-- Pilih --</option>
            @foreach (['Laki-laki', 'Perempuan'] as $jk)
                <option value="{{ $jk }}" @selected(old('jenis_kelamin', $murid?->jenis_kelamin) === $jk)>{{ $jk }}</option>
            @endforeach
        </select>
        @error('jenis_kelamin') <small class="galat">{{ $message }}</small> @enderror
    </div>
    <div class="isian">
        <label>Tanggal lahir</label>
        <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $murid?->tanggal_lahir?->toDateString()) }}">
        @error('tanggal_lahir') <small class="galat">{{ $message }}</small> @enderror
    </div>
    <div class="isian">
        <label>Jenjang dan kelas</label>
        <select name="jenjang">
            <option value="">-- Pilih --</option>
            @foreach ($jenjang as $j)
                <option value="{{ $j }}" @selected(old('jenjang', $murid?->jenjang) === $j)>{{ $j }}</option>
            @endforeach
        </select>
        @error('jenjang') <small class="galat">{{ $message }}</small> @enderror
    </div>
    <div class="isian">
        <label>Asal sekolah <span class="opsional">(opsional)</span></label>
        <input type="text" name="asal_sekolah" value="{{ old('asal_sekolah', $murid?->asal_sekolah) }}" maxlength="100">
        @error('asal_sekolah') <small class="galat">{{ $message }}</small> @enderror
    </div>
</div>
<div class="isian">
    <label>Catatan khusus untuk mentor <span class="opsional">(opsional)</span></label>
    <textarea name="catatan_khusus" rows="2" maxlength="500"
              placeholder="Contoh: masih kesulitan membaca, perlu pendampingan soal cerita">{{ old('catatan_khusus', $murid?->catatan_khusus) }}</textarea>
    @error('catatan_khusus') <small class="galat">{{ $message }}</small> @enderror
</div>

<h3 class="subjudul-form">Alamat tempat belajar</h3>
<p class="bantuan">Mentor akan datang ke alamat ini. Titik peta juga dipakai untuk memeriksa lokasi absensi mentor.</p>
<div class="isian">
    <label>Alamat</label>
    <textarea name="alamat" id="alamat" rows="2" placeholder="Contoh: Perum Griya Blok C-5, Mojoagung, Jombang">{{ old('alamat', $murid?->alamat) }}</textarea>
    @error('alamat') <small class="galat">{{ $message }}</small> @enderror
    <div class="baris-cari-alamat">
        <button type="button" id="tombol-cari-alamat" class="tombol kecil">Cari di Peta</button>
        <small class="bantuan">Pisahkan bagian alamat dengan koma (jalan, desa, kecamatan, kabupaten).</small>
    </div>
</div>
<label>Titik lokasi rumah</label>
<small class="bantuan">Klik "Cari di Peta", lalu klik pada peta atau geser penanda ke lokasi rumah yang tepat.</small>
<div id="peta-murid" class="peta"></div>
<small id="teks-titik" class="bantuan">Belum ada titik yang dipilih.</small>
<input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $murid?->latitude) }}">
<input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $murid?->longitude) }}">
@error('latitude') <small class="galat">{{ $message }}</small> @enderror

@push('script')
<script>
    (function () {
        const lat  = document.getElementById('latitude');
        const lng  = document.getElementById('longitude');
        const teks = document.getElementById('teks-titik');

        const adaTitik = lat.value !== '' && lng.value !== '';
        const awal = adaTitik ? [parseFloat(lat.value), parseFloat(lng.value)] : [-7.5650, 112.3400]; // Mojoagung

        const peta = L.map('peta-murid').setView(awal, adaTitik ? 16 : 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; OpenStreetMap'
        }).addTo(peta);

        let penanda = null;

        function setTitik(ll) {
            if (!penanda) {
                penanda = L.marker(ll, { draggable: true }).addTo(peta);
                penanda.on('dragend', () => setTitik(penanda.getLatLng()));
            } else {
                penanda.setLatLng(ll);
            }
            lat.value = ll.lat.toFixed(8);
            lng.value = ll.lng.toFixed(8);
            teks.textContent = 'Titik dipilih: ' + lat.value + ', ' + lng.value;
        }

        if (adaTitik) {
            setTitik(L.latLng(awal[0], awal[1]));
        }
        peta.on('click', e => setTitik(e.latlng));
        
        // Tombol "Cari di Peta": memindahkan peta ke lokasi alamat yang diketik
        hubungkanCariAlamat(document.getElementById('tombol-cari-alamat'),
            document.getElementById('alamat'), peta, setTitik, teks);
    })();
</script>
@endpush