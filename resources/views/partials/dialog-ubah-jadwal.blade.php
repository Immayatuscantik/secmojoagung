{{-- Pop-up ganti jadwal (pertemuan pengganti). $j = jadwal, $k = kursus.
     $mode = 'pengelola' (owner/admin langsung mengganti) atau 'mentor' (mengajukan, menunggu persetujuan admin). --}}
@php
    $mode      = $mode ?? 'pengelola';
    $bukaUlang = (string) old('id_jadwal_ubah') === (string) $j->id;
    $usulan    = $mode === 'pengelola' ? $j->pengajuanMenunggu() : null;
@endphp
<dialog id="ubah-jadwal-{{ $j->id }}" class="dialog {{ $bukaUlang ? 'buka-otomatis' : '' }}">
    <div class="kepala">
        <div>
            <h2>{{ $mode === 'mentor' ? 'Ajukan ganti hari' : 'Ganti jadwal' }} · pertemuan ke-{{ $j->pertemuan_ke }}</h2>
            <p class="sub">{{ $k->murid->nama }} · {{ $k->bidang->nama_bidang }} · {{ $j->tanggal->translatedFormat('l, d F Y') }},
                {{ substr($j->jam_mulai, 0, 5) }}–{{ substr($j->jam_selesai, 0, 5) }}</p>
        </div>
        <button type="button" class="tombol kecil tutup-dialog">Tutup</button>
    </div>

    @if ($usulan)
        <div class="notif sukses jarak-atas">
            Mentor mengajukan ganti ke {{ $usulan->uraianJadwalBaru() }}. Alasan: {{ $usulan->alasan }}.
            Proses pengajuan ini di menu Pengajuan Ganti Jadwal.
        </div>
    @endif

    <form method="POST" action="{{ $mode === 'mentor' ? route('mentor.jadwal.ajukan', $j) : route('owner.jadwal.ganti', $j) }}" class="formulir jarak-atas">
        @csrf
        @if ($mode === 'pengelola') @method('PUT') @endif
        <input type="hidden" name="id_jadwal_ubah" value="{{ $j->id }}">
        <p class="sub">
            @if ($mode === 'mentor')
                Usulkan tanggal dan jam pengganti yang sudah disepakati dengan wali murid. Jadwal baru berlaku setelah disetujui admin.
            @else
                Pertemuan dipindah ke tanggal baru dan langsung berlaku. Tanggal semula tetap tercatat.
            @endif
        </p>
        <label>Tanggal pengganti</label>
        <input type="date" name="tanggal" min="{{ today()->toDateString() }}" value="{{ $bukaUlang ? old('tanggal') : '' }}">
        @if ($bukaUlang) @error('tanggal') <small class="galat">{{ $message }}</small> @enderror @endif
        <div class="grid-2">
            <div class="isian">
                <label>Jam mulai</label>
                <input type="time" name="jam_mulai" value="{{ $bukaUlang ? old('jam_mulai') : substr($j->jam_mulai, 0, 5) }}">
                @if ($bukaUlang) @error('jam_mulai') <small class="galat">{{ $message }}</small> @enderror @endif
            </div>
            <div class="isian">
                <label>Jam selesai</label>
                <input type="time" name="jam_selesai" value="{{ $bukaUlang ? old('jam_selesai') : substr($j->jam_selesai, 0, 5) }}">
                @if ($bukaUlang) @error('jam_selesai') <small class="galat">{{ $message }}</small> @enderror @endif
            </div>
        </div>
        <label>Alasan penggantian</label>
        <input type="text" name="alasan" maxlength="255" value="{{ $bukaUlang ? old('alasan') : '' }}"
               placeholder="{{ $mode === 'mentor' ? 'Contoh: saya ada ujian kampus, sudah disepakati dengan wali' : 'Contoh: mentor sakit, diganti sesuai kesepakatan wali' }}">
        @if ($bukaUlang) @error('alasan') <small class="galat">{{ $message }}</small> @enderror @endif
        <div class="baris-tombol">
            <button type="submit" class="tombol utama">{{ $mode === 'mentor' ? 'Kirim Pengajuan' : 'Simpan Jadwal Pengganti' }}</button>
        </div>
    </form>
</dialog>
