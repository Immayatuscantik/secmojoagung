@extends('layouts.mentor')
@section('judul', 'Absensi')

@section('isi')
<div>
    <h1>Absensi Pertemuan</h1>
    <p class="sub">{{ $jadwal->kursus->murid->nama }} · {{ $jadwal->kursus->bidang->nama_bidang }} ·
        pertemuan {{ $jadwal->labelKe() }}</p>
</div>

<div class="notif {{ $terbuka ? 'sukses' : 'gagal' }}">
    Absensi dibuka pukul {{ $buka->format('H.i') }}–{{ $tutup->format('H.i') }}
    ({{ $jadwal->tanggal->translatedFormat('l, d F Y') }}).
    {{ $terbuka ? 'Absensi sedang dibuka.' : 'Saat ini di luar waktu absensi.' }}
</div>

<form method="POST" action="{{ route('mentor.absensi.store', $jadwal) }}" class="dua-kolom-sama"
      id="form-absensi"
      data-terbuka="{{ $terbuka ? '1' : '0' }}"
      data-lat="{{ $jadwal->kursus->murid->latitude }}"
      data-lng="{{ $jadwal->kursus->murid->longitude }}"
      data-radius="{{ $radius }}"
      data-radius-maks="{{ $radiusMaks }}">
    @csrf
    <input type="hidden" name="foto" id="foto">
    <input type="hidden" name="latitude" id="latitude">
    <input type="hidden" name="longitude" id="longitude">

    {{-- Swafoto --}}
    <section class="panel">
        <h2>1. Swafoto</h2>
        <video id="video" autoplay playsinline class="kamera"></video>
        <canvas id="kanvas" class="kamera tersembunyi"></canvas>
        <p id="info-kamera" class="bantuan">Mengaktifkan kamera...</p>
        <div class="baris-tombol">
            <button type="button" id="ambil" class="tombol">Ambil Swafoto</button>
            <button type="button" id="ulang" class="tombol tersembunyi">Ulangi</button>
        </div>
        @error('foto') <small class="galat">{{ $message }}</small> @enderror
    </section>

    {{-- Lokasi dan catatan --}}
    <section class="panel formulir">
        <h2>2. Lokasi</h2>
        <p id="info-lokasi" class="bantuan">Mengambil lokasi GPS...</p>
        <dl class="info-lokasi">
            <dt>Koordinat Anda</dt>        <dd id="teks-koordinat">-</dd>
            <dt>Jarak ke rumah murid</dt>  <dd id="teks-jarak">-</dd>
            <dt>Perkiraan status</dt>      <dd id="teks-status">-</dd>
        </dl>
        @error('latitude') <small class="galat">{{ $message }}</small> @enderror

        <h2 class="jarak-atas">3. Catatan materi</h2>
        <textarea name="catatan_materi" rows="4" placeholder="Materi yang diajarkan hari ini...">{{ old('catatan_materi') }}</textarea>
        @error('catatan_materi') <small class="galat">{{ $message }}</small> @enderror

        <button type="submit" id="kirim" class="tombol utama penuh jarak-atas" disabled>Kirim Absensi</button>
        <small id="syarat-kirim" class="bantuan"></small>
        <a href="{{ route('mentor.absensi') }}" class="tombol penuh jarak-atas-kecil">Kembali</a>
    </section>
</form>
@endsection

@push('script')
<script>
    const form    = document.getElementById('form-absensi');
    const terbuka = form.dataset.terbuka === '1';
    const rumah   = [parseFloat(form.dataset.lat), parseFloat(form.dataset.lng)];
    const radius  = parseFloat(form.dataset.radius);
    const radiusMaks = parseFloat(form.dataset.radiusMaks);
    let jarakSaya = null;

    const video  = document.getElementById('video');
    const kanvas = document.getElementById('kanvas');
    const foto   = document.getElementById('foto');
    const lat    = document.getElementById('latitude');
    const lng    = document.getElementById('longitude');
    const kirim  = document.getElementById('kirim');
    const ambil  = document.getElementById('ambil');
    const ulang  = document.getElementById('ulang');

    function tampil(el, ya) {
        el.classList.toggle('tersembunyi', !ya);
    }

    // Tombol Kirim aktif jika waktu absensi dibuka, swafoto sudah diambil, lokasi sudah didapat,
    // dan jarak tidak lebih dari batas maksimal. Jika belum, tampilkan apa saja yang masih kurang.
    function cekSiap() {
        const kurang = [];
        if (!terbuka)    kurang.push('di luar waktu absensi');
        if (!foto.value) kurang.push('ambil swafoto terlebih dahulu');
        if (!lat.value)  kurang.push('lokasi belum diperoleh');
        if (jarakSaya !== null && jarakSaya > radiusMaks) kurang.push('lokasi Anda lebih dari ' + radiusMaks + ' m dari rumah murid');

        kirim.disabled = kurang.length > 0;
        document.getElementById('syarat-kirim').textContent =
            kurang.length ? 'Belum bisa dikirim: ' + kurang.join(', ') + '.' : '';
    }
    cekSiap();

    // Rumus Haversine (hanya untuk tampilan; perhitungan resmi dilakukan di server)
    function haversine(a, b) {
        const R = 6371000;
        const rad = x => x * Math.PI / 180;
        const dLat = rad(b[0] - a[0]);
        const dLng = rad(b[1] - a[1]);
        const h = Math.sin(dLat / 2) ** 2
                + Math.cos(rad(a[0])) * Math.cos(rad(b[0])) * Math.sin(dLng / 2) ** 2;
        return 2 * R * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
    }

    // Kamera (R3)
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
        .then(stream => {
            video.srcObject = stream;
            document.getElementById('info-kamera').textContent = 'Kamera aktif. Tekan Ambil Swafoto.';
        })
        .catch(() => {
            document.getElementById('info-kamera').textContent = 'Izin kamera ditolak. Absensi tidak dapat dilakukan.';
            ambil.disabled = true;
        });

    ambil.addEventListener('click', () => {
        kanvas.width = video.videoWidth;
        kanvas.height = video.videoHeight;
        kanvas.getContext('2d').drawImage(video, 0, 0);
        foto.value = kanvas.toDataURL('image/jpeg', 0.7);
        tampil(video, false);
        tampil(kanvas, true);
        tampil(ambil, false);
        tampil(ulang, true);
        cekSiap();
    });

    ulang.addEventListener('click', () => {
        foto.value = '';
        tampil(video, true);
        tampil(kanvas, false);
        tampil(ambil, true);
        tampil(ulang, false);
        cekSiap();
    });

    // Lokasi GPS (R3)
    navigator.geolocation.getCurrentPosition(pos => {
        const saya = [pos.coords.latitude, pos.coords.longitude];
        lat.value = saya[0].toFixed(8);
        lng.value = saya[1].toFixed(8);
        const jarak = haversine(saya, rumah);
        jarakSaya = jarak;
        document.getElementById('info-lokasi').textContent = 'Lokasi berhasil diperoleh.';
        document.getElementById('teks-koordinat').textContent = lat.value + ', ' + lng.value;
        document.getElementById('teks-jarak').textContent = Math.round(jarak) + ' meter';
        document.getElementById('teks-status').textContent = jarak <= radius ? 'Valid'
            : (jarak <= radiusMaks ? 'Perlu Ditinjau' : 'Di luar jangkauan (tidak dapat absen)');
        cekSiap();
    }, () => {
        document.getElementById('info-lokasi').textContent = 'Izin lokasi ditolak. Absensi tidak dapat dilakukan.';
    }, { enableHighAccuracy: true, timeout: 15000 });
</script>
@endpush