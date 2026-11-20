{{-- Pop-up pertemuan tambahan tanpa biaya (owner/admin). $k = kursus --}}
@php $bukaUlang = (string) old('id_kursus_tambahan') === (string) $k->id; @endphp
<dialog id="tambah-pertemuan-{{ $k->id }}" class="dialog {{ $bukaUlang ? 'buka-otomatis' : '' }}">
    <div class="kepala">
        <div>
            <h2>Pertemuan tambahan · {{ $k->murid->nama }}</h2>
            <p class="sub">{{ $k->bidang->nama_bidang }} · mentor {{ $k->mentor?->user->nama }} · paket {{ $k->jumlah_pertemuan }} pertemuan</p>
        </div>
        <button type="button" class="tombol kecil tutup-dialog">Tutup</button>
    </div>

    <form method="POST" action="{{ route('owner.tambahan.store', $k) }}" class="formulir jarak-atas">
        @csrf
        <input type="hidden" name="id_kursus_tambahan" value="{{ $k->id }}">
        <p class="sub">Pertemuan di luar paket <strong>tanpa biaya</strong>, misalnya untuk mengulang materi yang dikeluhkan wali.
            Mentor tetap melakukan absensi (GPS, swafoto, catatan materi). Honor mentor tidak bertambah,
            dan honor baru dapat diajukan setelah pertemuan tambahan dilaksanakan.</p>
        <label>Tanggal</label>
        <input type="date" name="tanggal" min="{{ today()->toDateString() }}" value="{{ $bukaUlang ? old('tanggal') : '' }}">
        @if ($bukaUlang) @error('tanggal') <small class="galat">{{ $message }}</small> @enderror @endif
        <div class="grid-2">
            <div class="isian">
                <label>Jam mulai</label>
                <input type="time" name="jam_mulai" value="{{ $bukaUlang ? old('jam_mulai') : substr($k->jadwal->first()?->jam_mulai, 0, 5) }}">
                @if ($bukaUlang) @error('jam_mulai') <small class="galat">{{ $message }}</small> @enderror @endif
            </div>
            <div class="isian">
                <label>Jam selesai</label>
                <input type="time" name="jam_selesai" value="{{ $bukaUlang ? old('jam_selesai') : substr($k->jadwal->first()?->jam_selesai, 0, 5) }}">
                @if ($bukaUlang) @error('jam_selesai') <small class="galat">{{ $message }}</small> @enderror @endif
            </div>
        </div>
        <label>Alasan</label>
        <input type="text" name="alasan" maxlength="255" value="{{ $bukaUlang ? old('alasan') : '' }}"
               placeholder="Contoh: mengulang materi pertemuan ke-12, murid sakit (kesepakatan diskusi verifikasi)">
        @if ($bukaUlang) @error('alasan') <small class="galat">{{ $message }}</small> @enderror @endif
        <div class="baris-tombol">
            <button type="submit" class="tombol utama">Jadwalkan Pertemuan Tambahan</button>
        </div>
    </form>
</dialog>
