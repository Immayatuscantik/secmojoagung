@extends('layouts.owner')
@section('judul', 'Tambah Murid')

@section('isi')
@php $jenisWali = old('jenis_wali', 'baru'); @endphp

<div>
    <h1>Tambah Murid</h1>
    <p class="sub">Dipakai jika wali meminta admin untuk mendaftarkan anaknya. Perubahan tercatat di Riwayat Aktivitas.</p>
</div>

<form method="POST" action="{{ route('owner.murid.store') }}" class="panel formulir lebar">
    @csrf

    {{-- A. Wali --}}
    <h2>A. Data wali</h2>
    <div class="pilihan-wali">
        <label><input type="radio" name="jenis_wali" value="baru" @checked($jenisWali === 'baru')> Wali baru (buatkan akun)</label>
        <label><input type="radio" name="jenis_wali" value="lama" @checked($jenisWali === 'lama')> Wali sudah terdaftar</label>
    </div>
    @error('jenis_wali') <small class="galat">{{ $message }}</small> @enderror

    <div id="wali-lama" class="{{ $jenisWali === 'lama' ? '' : 'tersembunyi' }}">
        <div class="isian">
            <label>Pilih wali</label>
            <select name="id_wali">
                <option value="">-- Pilih wali --</option>
                @foreach ($wali as $w)
                    <option value="{{ $w->id }}" @selected(old('id_wali') == $w->id)>
                        {{ $w->user->nama }} ({{ $w->hubungan }}) · {{ $w->user->no_hp }}
                    </option>
                @endforeach
            </select>
            @error('id_wali') <small class="galat">{{ $message }}</small> @enderror
        </div>
    </div>

    <div id="wali-baru" class="{{ $jenisWali === 'baru' ? '' : 'tersembunyi' }}">
        <div class="grid-2">
            <div class="isian">
                <label>Nama wali</label>
                <input type="text" name="wali_nama" value="{{ old('wali_nama') }}" maxlength="50">
                @error('wali_nama') <small class="galat">{{ $message }}</small> @enderror
            </div>
            <div class="isian">
                <label>Hubungan dengan murid</label>
                <select name="wali_hubungan">
                    <option value="">-- Pilih --</option>
                    @foreach (['Ayah', 'Ibu', 'Wali'] as $h)
                        <option value="{{ $h }}" @selected(old('wali_hubungan') === $h)>{{ $h }}</option>
                    @endforeach
                </select>
                @error('wali_hubungan') <small class="galat">{{ $message }}</small> @enderror
            </div>
            <div class="isian">
                <label>No. HP (WhatsApp)</label>
                <input type="text" name="wali_no_hp" value="{{ old('wali_no_hp') }}" maxlength="15" placeholder="08xxxxxxxxxx">
                @error('wali_no_hp') <small class="galat">{{ $message }}</small> @enderror
            </div>
            <div class="isian">
                <label>Email (untuk login wali)</label>
                <input type="email" name="wali_email" value="{{ old('wali_email') }}" maxlength="50">
                @error('wali_email') <small class="galat">{{ $message }}</small> @enderror
            </div>
            <div class="isian">
                <label>Pekerjaan <span class="opsional">(opsional)</span></label>
                <input type="text" name="wali_pekerjaan" value="{{ old('wali_pekerjaan') }}" maxlength="50">
            </div>
        </div>
        <p class="bantuan">Akun wali dibuat otomatis dengan <strong>kata sandi awal = nomor HP</strong>. Alamat wali disamakan dengan alamat murid.</p>
    </div>

    {{-- B. Data murid (judul bagian berasal dari partial) --}}
    <h2 class="jarak-atas">B. Data murid</h2>
    @include('partials.murid-form')

    {{-- C. Kursus pertama (opsional) --}}
    <h2 class="jarak-atas">C. Kursus pertama <span class="opsional">(opsional)</span></h2>
    <p class="bantuan">Kosongkan jika kursus akan ditambahkan nanti. Kursus yang ditambahkan admin langsung tampil di Daftar Job mentor.</p>
    @include('partials.kursus-form-fields')

    <div class="baris-tombol kanan">
        <a href="{{ route('owner.murid.index') }}" class="tombol">Batal</a>
        <button type="submit" class="tombol utama">Simpan Murid</button>
    </div>
</form>
@endsection

@push('script')
<script>
    // Menampilkan isian wali sesuai pilihan "Wali baru" atau "Wali sudah terdaftar"
    document.querySelectorAll('input[name="jenis_wali"]').forEach(r => r.addEventListener('change', () => {
        const baru = document.querySelector('input[name="jenis_wali"]:checked').value === 'baru';
        document.getElementById('wali-baru').classList.toggle('tersembunyi', !baru);
        document.getElementById('wali-lama').classList.toggle('tersembunyi', baru);
    }));
</script>
@endpush