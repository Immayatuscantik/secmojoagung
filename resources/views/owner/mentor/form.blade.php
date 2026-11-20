@extends('layouts.owner')
@section('judul', $mentor ? 'Ubah Data Mentor' : 'Tambah Mentor')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/cari-alamat.js') }}"></script>
@endpush

@section('isi')
@php
    $user = $mentor?->user;
    // Keahlian terpilih: [id_bidang => ['SD', 'SMP'], ...]
    $keahlianTerpilih = old('keahlian', $mentor
        ? $mentor->keahlian->groupBy('id_bidang')->map(fn ($k) => $k->pluck('tingkat')->all())->all()
        : []);
@endphp

<div>
    <h1>{{ $mentor ? 'Ubah Data Mentor' : 'Tambah Mentor' }}</h1>
    <p class="sub">Lengkapi akun login, profil pribadi, bidang yang dikuasai, dan alamat rumah mentor.</p>
</div>

<form method="POST" class="panel formulir lebar"
      action="{{ $mentor ? route('owner.mentor.update', $mentor) : route('owner.mentor.store') }}">
    @csrf
    @if ($mentor) @method('PUT') @endif

    <h2>1. Akun login</h2>
    <div class="grid-2">
        <div class="isian">
            <label>Nama lengkap</label>
            <input type="text" name="nama" value="{{ old('nama', $user?->nama) }}" maxlength="50">
            @error('nama') <small class="galat">{{ $message }}</small> @enderror
        </div>
        <div class="isian">
            <label>No. HP</label>
            <input type="text" name="no_hp" value="{{ old('no_hp', $user?->no_hp) }}" maxlength="15">
            @error('no_hp') <small class="galat">{{ $message }}</small> @enderror
        </div>
        <div class="isian">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $user?->email) }}" maxlength="50">
            @error('email') <small class="galat">{{ $message }}</small> @enderror
        </div>
        <div class="isian">
            <label>Kata sandi</label>
            <input type="password" name="password" maxlength="15">
            <small class="bantuan">{{ $mentor ? 'Kosongkan jika tidak ingin mengubah kata sandi.' : '10–15 karakter.' }}</small>
            @error('password') <small class="galat">{{ $message }}</small> @enderror
        </div>
    </div>

    <h2>2. Profil pribadi</h2>
    <div class="grid-2">
        <div class="isian">
            <label>Jenis kelamin</label>
            <select name="jenis_kelamin">
                <option value="">-- Pilih --</option>
                @foreach (['Laki-laki', 'Perempuan'] as $jk)
                    <option value="{{ $jk }}" @selected(old('jenis_kelamin', $mentor?->jenis_kelamin) === $jk)>{{ $jk }}</option>
                @endforeach
            </select>
            @error('jenis_kelamin') <small class="galat">{{ $message }}</small> @enderror
        </div>
        <div class="isian">
            <label>Tanggal lahir</label>
            <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $mentor?->tanggal_lahir?->toDateString()) }}">
            @error('tanggal_lahir') <small class="galat">{{ $message }}</small> @enderror
        </div>
        <div class="isian">
            <label>Pendidikan terakhir</label>
            <select name="pendidikan_terakhir">
                <option value="">-- Pilih --</option>
                @foreach ($pendidikan as $p)
                    <option value="{{ $p }}" @selected(old('pendidikan_terakhir', $mentor?->pendidikan_terakhir) === $p)>{{ $p }}</option>
                @endforeach
            </select>
            @error('pendidikan_terakhir') <small class="galat">{{ $message }}</small> @enderror
        </div>
        <div class="isian">
            <label>Asal institusi (sekolah/kampus)</label>
            <input type="text" name="asal_institusi" value="{{ old('asal_institusi', $mentor?->asal_institusi) }}" maxlength="100">
            @error('asal_institusi') <small class="galat">{{ $message }}</small> @enderror
        </div>
    </div>
    <div class="isian">
        <label>Pengalaman mengajar <span class="opsional">(opsional)</span></label>
        <textarea name="pengalaman_mengajar" rows="2" placeholder="Contoh: mengajar les privat Matematika SD selama 2 tahun">{{ old('pengalaman_mengajar', $mentor?->pengalaman_mengajar) }}</textarea>
        @error('pengalaman_mengajar') <small class="galat">{{ $message }}</small> @enderror
    </div>

    <h2>3. Bidang dan tingkat yang dikuasai</h2>
    <p class="bantuan">Centang tingkat yang sanggup diajar pada setiap bidang. Mentor hanya akan melihat job yang bidang dan tingkatnya dicentang.</p>
    <table class="tabel tabel-keahlian">
        <thead><tr><th>Bidang</th><th>Tingkat yang diajar</th></tr></thead>
        <tbody>
            @foreach ($bidang as $b)
                <tr>
                    <td><strong>{{ $b->nama_bidang }}</strong></td>
                    <td>
                        <div class="hari">
                            @forelse ($b->tingkatTersedia() as $t)
                                <label>
                                    <input type="checkbox" name="keahlian[{{ $b->id }}][]" value="{{ $t }}"
                                           @checked(in_array($t, $keahlianTerpilih[$b->id] ?? []))>
                                    {{ $t }}
                                </label>
                            @empty
                                <span class="sub">Belum ada tarif honor untuk bidang ini.</span>
                            @endforelse
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @error('keahlian_sah') <small class="galat">{{ $message }}</small> @enderror

    <h2>4. Alamat rumah</h2>
    <div class="isian">
        <label>Alamat</label>
        <textarea name="alamat" id="alamat" rows="2" placeholder="Contoh: Jl. Raya No. 10, Kademangan, Mojoagung, Jombang">{{ old('alamat', $mentor?->alamat) }}</textarea>
        @error('alamat') <small class="galat">{{ $message }}</small> @enderror
        <div class="baris-cari-alamat">
            <button type="button" id="tombol-cari-alamat" class="tombol kecil">Cari di Peta</button>
            <small class="bantuan">Pisahkan bagian alamat dengan koma (jalan, desa, kecamatan, kabupaten).</small>
        </div>
    </div>
    <label>Titik lokasi rumah</label>
    <small class="bantuan">Klik "Cari di Peta", lalu klik pada peta atau geser penanda ke lokasi rumah mentor yang tepat.</small>
    <div id="peta-mentor" class="peta"></div>
    <small id="teks-titik" class="bantuan">Belum ada titik yang dipilih.</small>
    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $mentor?->latitude) }}">
    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $mentor?->longitude) }}">
    @error('latitude') <small class="galat">{{ $message }}</small> @enderror

    <div class="baris-tombol kanan">
        <a href="{{ $mentor ? route('owner.mentor.show', $mentor) : route('owner.mentor.index') }}" class="tombol">Batal</a>
        <button type="submit" class="tombol utama">{{ $mentor ? 'Simpan Perubahan' : 'Simpan Mentor' }}</button>
    </div>
</form>
@endsection

@push('script')
<script>
    const lat  = document.getElementById('latitude');
    const lng  = document.getElementById('longitude');
    const teks = document.getElementById('teks-titik');

    const adaTitik = lat.value !== '' && lng.value !== '';
    const awal = adaTitik ? [parseFloat(lat.value), parseFloat(lng.value)] : [-7.5650, 112.3400]; // Mojoagung

    const peta = L.map('peta-mentor').setView(awal, adaTitik ? 16 : 13);
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
</script>
@endpush