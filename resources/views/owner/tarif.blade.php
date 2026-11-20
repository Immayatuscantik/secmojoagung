@extends('layouts.owner')
@section('judul', 'Tarif Honor')

@section('isi')
<div>
    <h1>Tarif Honor Mentor</h1>
    <p class="sub">Honor yang dibayarkan bimbel kepada mentor (bukan biaya les wali). Honor setiap kursus dihitung otomatis dari tarif ini saat wali mendaftar.</p>
</div>

<div class="dua-kolom">
    {{-- Daftar tarif --}}
    <section class="panel">
        <table class="tabel">
            <thead>
                <tr><th>Bidang</th><th>Tingkat</th><th>Jenis</th><th>Honor</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($tarif as $t)
                    <tr>
                        <td>{{ $t->bidang->nama_bidang }}</td>
                        <td>{{ $t->tingkat }}</td>
                        <td>{{ $t->keterangan() }}</td>
                        <td>{{ \App\Support\Rupiah::format($t->nominal) }}{{ $t->jenis === 'per_pertemuan' ? ' / pertemuan' : '' }}</td>
                        <td class="aksi">
                            <a href="{{ route('owner.tarif.index', ['edit' => $t->id]) }}" class="tombol kecil">Ubah</a>
                            <form method="POST" action="{{ route('owner.tarif.destroy', $t) }}"
                                  onsubmit="return confirm('Hapus tarif ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="tombol kecil bahaya">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="kosong">Belum ada tarif.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    {{-- Form tambah / ubah --}}
    <section class="panel formulir">
        <h2>{{ $edit ? 'Ubah Tarif' : 'Tambah Tarif' }}</h2>

        <form method="POST" action="{{ $edit ? route('owner.tarif.update', $edit) : route('owner.tarif.store') }}">
            @csrf
            @if ($edit) @method('PUT') @endif

            <label>Bidang</label>
            <select name="id_bidang">
                <option value="">-- Pilih --</option>
                @foreach ($bidang as $b)
                    <option value="{{ $b->id }}" @selected(old('id_bidang', $edit?->id_bidang) == $b->id)>{{ $b->nama_bidang }}</option>
                @endforeach
            </select>
            @error('id_bidang') <small class="galat">{{ $message }}</small> @enderror

            <label>Tingkat</label>
            <select name="tingkat">
                <option value="">-- Pilih --</option>
                @foreach ($tingkat as $t)
                    <option value="{{ $t }}" @selected(old('tingkat', $edit?->tingkat) === $t)>{{ $t }}</option>
                @endforeach
            </select>
            @error('tingkat') <small class="galat">{{ $message }}</small> @enderror

            <label>Jenis tarif</label>
            <select name="jenis">
                <option value="">-- Pilih --</option>
                <option value="paket" @selected(old('jenis', $edit?->jenis) === 'paket')>Paket (honor tetap per paket)</option>
                <option value="per_pertemuan" @selected(old('jenis', $edit?->jenis) === 'per_pertemuan')>Per pertemuan</option>
            </select>
            @error('jenis') <small class="galat">{{ $message }}</small> @enderror

            <label>Jumlah pertemuan paket</label>
            <input type="number" name="jumlah_pertemuan" min="1" max="50"
                   value="{{ old('jumlah_pertemuan', $edit?->jumlah_pertemuan) }}" placeholder="Contoh: 8">
            <small class="bantuan">Diisi hanya untuk jenis paket. Paket berlaku 4 pekan: 8 = 2× per pekan, 12 = 3×, dst.</small>
            @error('jumlah_pertemuan') <small class="galat">{{ $message }}</small> @enderror

            <label>Nominal honor (Rp)</label>
            <input type="number" name="nominal" min="1000" step="500" value="{{ old('nominal', $edit?->nominal) }}" placeholder="Contoh: 150000">
            @error('nominal') <small class="galat">{{ $message }}</small> @enderror

            <p class="bantuan jarak-atas-kiri">Perubahan tarif hanya berlaku untuk kursus yang didaftarkan setelahnya.</p>

            <div class="baris-tombol">
                <button type="submit" class="tombol utama">{{ $edit ? 'Simpan Perubahan' : 'Tambah Tarif' }}</button>
                @if ($edit)
                    <a href="{{ route('owner.tarif.index') }}" class="tombol">Batal</a>
                @endif
            </div>
        </form>
    </section>
</div>
@endsection