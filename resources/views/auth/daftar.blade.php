@extends('layouts.tamu')
@section('judul', 'Daftar Wali Murid')
@section('sambutan-judul', 'Daftarkan putra-putri Anda')
@section('sambutan-teks', 'Buat akun wali murid untuk mendaftarkan anak, memilih kursus, dan memantau perkembangan belajarnya.')

@section('isi')
<form method="POST" action="{{ route('daftar.proses') }}" class="formulir">
    @csrf
    <h2 class="text-2xl font-bold">Daftar akun wali murid</h2>
    <p class="sub mb-4">Isi data diri Anda sebagai orang tua/wali. Data anak diisi setelah akun dibuat.</p>

    <div class="isian">
        <label for="nama">Nama lengkap</label>
        <input type="text" id="nama" name="nama" value="{{ old('nama') }}" maxlength="50">
        @error('nama') <small class="galat">{{ $message }}</small> @enderror
    </div>

    <div class="grid gap-x-4 sm:grid-cols-2">
        <div class="isian">
            <label for="hubungan">Hubungan dengan murid</label>
            <select id="hubungan" name="hubungan">
                <option value="">-- Pilih --</option>
                @foreach (['Ayah', 'Ibu', 'Wali'] as $h)
                    <option value="{{ $h }}" @selected(old('hubungan') === $h)>{{ $h }}</option>
                @endforeach
            </select>
            @error('hubungan') <small class="galat">{{ $message }}</small> @enderror
        </div>
        <div class="isian">
            <label for="pekerjaan">Pekerjaan <span class="opsional">(opsional)</span></label>
            <input type="text" id="pekerjaan" name="pekerjaan" value="{{ old('pekerjaan') }}" maxlength="50">
            @error('pekerjaan') <small class="galat">{{ $message }}</small> @enderror
        </div>
    </div>

    <div class="isian">
        <label for="no_hp">No. HP (WhatsApp)</label>
        <input type="text" id="no_hp" name="no_hp" value="{{ old('no_hp') }}" maxlength="15" placeholder="08xxxxxxxxxx" inputmode="tel">
        @error('no_hp') <small class="galat">{{ $message }}</small> @enderror
    </div>

    <div class="isian">
        <label for="alamat">Alamat</label>
        <textarea id="alamat" name="alamat" rows="2" placeholder="Nama jalan, RT/RW, desa, kecamatan">{{ old('alamat') }}</textarea>
        @error('alamat') <small class="galat">{{ $message }}</small> @enderror
    </div>

    <div class="isian">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="50" placeholder="nama@email.com">
        @error('email') <small class="galat">{{ $message }}</small> @enderror
    </div>

    <div class="grid gap-x-4 sm:grid-cols-2">
        <div class="isian">
            <label for="password">Kata sandi</label>
            <input type="password" id="password" name="password" maxlength="15" placeholder="10–15 karakter">
            @error('password') <small class="galat">{{ $message }}</small> @enderror
        </div>
        <div class="isian">
            <label for="password_confirmation">Ulangi kata sandi</label>
            <input type="password" id="password_confirmation" name="password_confirmation" maxlength="15">
        </div>
    </div>

    <button type="submit" class="tombol utama mt-6 w-full py-3 text-[15px]">Daftar</button>
    <p class="mt-4 text-center text-sm text-teks-sedang">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold">Masuk</a></p>
</form>
@endsection