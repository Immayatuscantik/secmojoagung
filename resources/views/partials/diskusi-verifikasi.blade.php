{{-- Diskusi verifikasi pertemuan: keberatan wali, tanggapan mentor, penengahan owner/admin.
     $k = kursus (dengan relasi tanggapan.user). Tampil jika wali pernah menyatakan belum sesuai. --}}
@if ($k->adaDiskusi())
    @php
        $bukaForm  = (string) old('id_kursus_tanggapan') === (string) $k->id;
        $petunjuk  = match (auth()->user()->peran) {
            'mentor' => 'Jelaskan kepada wali apa yang terjadi atau tawarkan solusi, misalnya pertemuan tambahan.',
            'wali'   => 'Balas tanggapan mentor. Jika penjelasan sudah dapat diterima, klik Setujui pada kotak verifikasi.',
            default  => 'Sebagai penengah, berikan klarifikasi atau keputusan agar wali dan mentor menemukan jalan keluar.',
        };
    @endphp
    <div class="diskusi" id="diskusi-{{ $k->id }}">
        <h3>Diskusi verifikasi
            @if ($k->diskusiTerbuka())
                <span class="label tidak-hadir">Menunggu penyelesaian</span>
            @else
                <span class="label diambil">Selesai</span>
            @endif
        </h3>
        <p class="sub">Riwayat keberatan wali, tanggapan mentor, dan catatan admin. Dapat dipantau oleh owner dan admin.</p>

        <div class="diskusi-daftar">
            @foreach ($k->tanggapan as $t)
                <div class="pesan {{ $t->jenis }} {{ $t->id_user === auth()->id() ? 'dari-saya' : '' }}">
                    <div class="pesan-kepala">
                        <strong>{{ $t->user->nama }}</strong>
                        <span>{{ $t->namaPeran() }}</span>
                        <span>{{ $t->created_at->translatedFormat('d M Y, H.i') }}</span>
                        @if ($t->jenis === 'keberatan')
                            <span class="label tidak-hadir">Belum sesuai</span>
                        @elseif ($t->jenis === 'persetujuan')
                            <span class="label diambil">Disetujui</span>
                        @endif
                    </div>
                    <p>{{ $t->isi }}</p>
                </div>
            @endforeach
        </div>

        @if ($k->diskusiTerbuka())
            <form method="POST" action="{{ route('tanggapan.store', $k) }}" class="formulir jarak-atas">
                @csrf
                <input type="hidden" name="id_kursus_tanggapan" value="{{ $k->id }}">
                <label>Tulis tanggapan</label>
                <textarea name="isi" rows="2" maxlength="1000" placeholder="{{ $petunjuk }}">{{ $bukaForm ? old('isi') : '' }}</textarea>
                @if ($bukaForm) @error('isi') <small class="galat">{{ $message }}</small> @enderror @endif
                <div class="baris-tombol">
                    <button type="submit" class="tombol utama kecil">Kirim Tanggapan</button>
                </div>
            </form>
        @endif
    </div>
@endif
