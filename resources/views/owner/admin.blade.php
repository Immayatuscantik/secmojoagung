@extends('layouts.owner')
@section('judul', 'Kelola Admin')

@section('isi')
<div>
    <h1>Kelola Admin</h1>
    <p class="sub">Admin memiliki akses yang sama dengan owner untuk mengelola data. Setiap tindakannya tercatat di Riwayat Aktivitas.</p>
</div>

<div class="dua-kolom-sama">
    {{-- Daftar admin --}}
    <section class="panel">
        <table class="tabel">
            <thead>
                <tr><th>Nama</th><th>Kontak</th><th>Aktivitas</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($admin as $a)
                    <tr>
                        <td>{{ $a->nama }}</td>
                        <td>{{ $a->email }}<br><span class="sub">{{ $a->no_hp }}</span></td>
                        <td>{{ $a->log_aktivitas_count }}</td>
                        <td class="aksi">
                            <a href="{{ route('owner.admin.index', ['edit' => $a->id]) }}" class="tombol kecil">Ubah</a>
                            <form method="POST" action="{{ route('owner.admin.destroy', $a) }}"
                                  onsubmit="return confirm('Hapus akun admin {{ $a->nama }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="tombol kecil bahaya">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="kosong">Belum ada akun admin.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    {{-- Form tambah / ubah --}}
    <section class="panel formulir">
        <h2>{{ $edit ? 'Ubah Data Admin' : 'Tambah Admin' }}</h2>

        <form method="POST" action="{{ $edit ? route('owner.admin.update', $edit) : route('owner.admin.store') }}">
            @csrf
            @if ($edit) @method('PUT') @endif

            <label>Nama</label>
            <input type="text" name="nama" value="{{ old('nama', $edit?->nama) }}" maxlength="50">
            @error('nama') <small class="galat">{{ $message }}</small> @enderror

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $edit?->email) }}" maxlength="50">
            @error('email') <small class="galat">{{ $message }}</small> @enderror

            <label>No. HP</label>
            <input type="text" name="no_hp" value="{{ old('no_hp', $edit?->no_hp) }}" maxlength="15">
            @error('no_hp') <small class="galat">{{ $message }}</small> @enderror

            <label>Kata sandi</label>
            <input type="password" name="password" maxlength="15">
            <small class="bantuan">{{ $edit ? 'Kosongkan jika tidak ingin mengubah kata sandi.' : '10–15 karakter.' }}</small>
            @error('password') <small class="galat">{{ $message }}</small> @enderror

            <div class="baris-tombol">
                <button type="submit" class="tombol utama">{{ $edit ? 'Simpan Perubahan' : 'Tambah Admin' }}</button>
                @if ($edit)
                    <a href="{{ route('owner.admin.index') }}" class="tombol">Batal</a>
                @endif
            </div>
        </form>
    </section>
</div>
@endsection