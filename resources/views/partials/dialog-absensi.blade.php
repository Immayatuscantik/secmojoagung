{{-- Pop-up detail absensi untuk mentor.
     $a = absensi (dengan relasi jadwal dan riwayatCatatan), $bisaEdit = boolean --}}
<dialog id="absensi-{{ $a->id }}" class="dialog {{ (string) old('id_absensi') === (string) $a->id ? 'buka-otomatis' : '' }}">
    <div class="kepala">
        <h2>Detail Absensi · Pertemuan {{ $a->jadwal->labelKe() }}</h2>
        <button type="button" class="tombol kecil tutup-dialog">Tutup</button>
    </div>

    <div class="dua-kolom-sama jarak-atas-kiri">
        <div>
            <img src="{{ asset('storage/' . $a->foto) }}" alt="Swafoto" class="kamera">
        </div>
        <dl class="info-lokasi">
            <dt>Jadwal</dt>      <dd>{{ $a->jadwal->tanggal->translatedFormat('l, d F Y') }}, {{ substr($a->jadwal->jam_mulai, 0, 5) }}–{{ substr($a->jadwal->jam_selesai, 0, 5) }}</dd>
            <dt>Waktu absen</dt> <dd>{{ $a->waktu_absen->translatedFormat('d F Y, H.i') }} WIB</dd>
            <dt>Jarak</dt>       <dd>{{ number_format($a->jarak, 0, ',', '.') }} meter</dd>
            <dt>Status</dt>      <dd><span class="label {{ $a->status === 'Valid' ? 'diambil' : 'tersedia' }}">{{ $a->status }}</span></dd>
        </dl>
    </div>

    <h3 class="jarak-atas">Catatan materi</h3>
    @if ($bisaEdit)
        <form method="POST" action="{{ route('mentor.absensi.catatan', $a) }}" class="formulir">
            @csrf
            @method('PUT')
            <input type="hidden" name="id_absensi" value="{{ $a->id }}">
            <textarea name="catatan_materi" rows="3" maxlength="1000">{{ (string) old('id_absensi') === (string) $a->id ? old('catatan_materi') : $a->catatan_materi }}</textarea>
            @if ((string) old('id_absensi') === (string) $a->id)
                @error('catatan_materi') <small class="galat">{{ $message }}</small> @enderror
            @endif
            <div class="baris-tombol">
                <button type="submit" class="tombol utama kecil">Simpan Perubahan Catatan</button>
            </div>
        </form>
    @else
        <p>{{ $a->catatan_materi }}</p>
        <p class="bantuan">Catatan tidak dapat diubah karena honor kursus ini sudah diajukan.</p>
    @endif

    <h3 class="jarak-atas">Riwayat perubahan catatan</h3>
    @forelse ($a->riwayatCatatan as $r)
        <div class="riwayat-catatan">
            <span class="sub">Diubah {{ $r->created_at->translatedFormat('d F Y, H.i') }} WIB</span>
            <p><s>{{ $r->catatan_lama }}</s></p>
            <p>→ {{ $r->catatan_baru }}</p>
        </div>
    @empty
        <p class="bantuan">Catatan belum pernah diubah.</p>
    @endforelse
</dialog>
