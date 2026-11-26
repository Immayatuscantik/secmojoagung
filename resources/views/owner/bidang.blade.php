@extends('layouts.owner')
@section('judul', 'Data Bidang')

@section('isi')
<div>
    <h1>Data Bidang</h1>
    <p class="sub">Daftar bidang/kursus yang ditawarkan bimbel. Bidang dipakai saat mengisi profil mentor dan saat wali mendaftarkan kursus.</p>
</div>

<div class="dua-kolom-sama tata-bidang">
    {{-- Daftar bidang --}}
    <section class="panel">
        <div class="gulir-tabel">
        <table class="tabel tabel-bidang">
            <thead>
                <tr><th>Bidang</th><th>Tingkat</th><th>Mentor</th><th>Kursus</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($bidang as $b)
                    <tr>
                        <td><strong>{{ $b->nama_bidang }}</strong><br><span class="sub">{{ $b->keterangan ?? '-' }}</span></td>
                        <td>
                            @if ($b->tarifHonor->contains('tingkat', 'Semua'))
                                <span class="label">Semua tingkat</span>
                            @else
                                @forelse ($b->tingkatTersedia() as $t)
                                    <span class="label">{{ $t }}</span>
                                @empty
                                    <span class="sub">Belum ada tarif</span>
                                @endforelse
                            @endif
                        </td>
                        <td>{{ $b->mentor_count }}</td>
                        <td>{{ $b->kursus_count }}</td>
                        <td class="aksi">
                            <a href="{{ route('owner.bidang.index', ['edit' => $b->id]) }}" class="tombol kecil">Ubah</a>
                            <form method="POST" action="{{ route('owner.bidang.destroy', $b) }}"
                                  onsubmit="return confirm('Hapus bidang {{ $b->nama_bidang }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="tombol kecil bahaya">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="kosong">Belum ada bidang.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </section>

    {{-- Form tambah / ubah --}}
    <section class="panel formulir">
        <h2>{{ $edit ? 'Ubah Bidang' : 'Tambah Bidang' }}</h2>

        <form method="POST" action="{{ $edit ? route('owner.bidang.update', $edit) : route('owner.bidang.store') }}">
            @csrf
            @if ($edit) @method('PUT') @endif

            <label>Nama bidang</label>
            <input type="text" name="nama_bidang" value="{{ old('nama_bidang', $edit?->nama_bidang) }}" maxlength="50"
                   placeholder="Contoh: Matematika">
            @error('nama_bidang') <small class="galat">{{ $message }}</small> @enderror

            <label>Keterangan <span class="opsional">(opsional)</span></label>
            <textarea name="keterangan" rows="2" maxlength="255" placeholder="Contoh: Matematika SD, SMP, dan SMA">{{ old('keterangan', $edit?->keterangan) }}</textarea>
            @error('keterangan') <small class="galat">{{ $message }}</small> @enderror

            <p class="bantuan jarak-atas-kiri">Tingkat setiap bidang diambil otomatis dari menu <a href="{{ route('owner.tarif.index') }}">Tarif Honor</a>.
                Setelah menambah bidang baru, tambahkan tarifnya agar bidang dapat dipilih wali dan mentor.</p>

            <div class="baris-tombol">
                <button type="submit" class="tombol utama">{{ $edit ? 'Simpan Perubahan' : 'Tambah Bidang' }}</button>
                @if ($edit)
                    <a href="{{ route('owner.bidang.index') }}" class="tombol">Batal</a>
                @endif
            </div>
        </form>
    </section>
</div>
@endsection

@push('head')
<style>
    /* Daftar bidang lebih lebar dari formulir agar kolom Aksi tidak keluar dari kotak */
    @media (min-width: 1024px) { .dua-kolom-sama.tata-bidang { grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr); } }
    .tabel-bidang td.aksi { width: 1%; }
    .tabel-bidang td.aksi > * { display: flex; margin: 0 0 6px; }
    .tabel-bidang td.aksi > *:last-child { margin-bottom: 0; }
    .tabel-bidang td.aksi .tombol { width: 100%; justify-content: center; }
    .tabel-bidang td.aksi form { width: 100%; }
</style>
@endpush
