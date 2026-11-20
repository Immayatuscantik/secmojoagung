{{-- Status honor sebuah kursus di halaman mentor. $k = kursus (dengan pengajuanHonor dan hadir_count) --}}
@php
    $p       = $k->pengajuanTerakhir();
    $lengkap = $k->pertemuanLengkap();
@endphp

<div class="aksi-honor">
    @if (! $lengkap)
        <span class="sub">Ajukan honor tersedia setelah {{ $k->jumlah_pertemuan }} pertemuan terpenuhi dan diverifikasi wali.</span>
    @elseif ($k->verifikasi_wali === 'belum')
        <span class="label">Menunggu verifikasi wali</span>
        <span class="sub">Seluruh pertemuan terpenuhi. Wali murid perlu memverifikasi sebelum honor dapat diajukan.</span>
    @elseif ($k->verifikasi_wali === 'belum_sesuai')
        <div class="notif gagal penuh-lebar">
            <span>Wali menyatakan pertemuan <strong>belum sesuai</strong>: "{{ rtrim($k->alasan_wali, '.') }}".
            @if (request()->routeIs('mentor.murid.show'))
                Berikan tanggapan pada bagian Verifikasi wali di bawah. Honor dapat diajukan setelah wali menyetujui.
            @else
                <a href="{{ route('mentor.murid.show', $k) }}#diskusi-{{ $k->id }}">Beri tanggapan</a>. Honor dapat diajukan setelah wali menyetujui.
            @endif
            </span>
        </div>
    @elseif (! $p && ($sisa = $k->tambahanBelumSelesai()->first()))
        <span class="label tersedia">Pertemuan tambahan</span>
        <span class="sub">Ada pertemuan tambahan tanpa biaya pada {{ $sisa->tanggal->translatedFormat('l, d F Y') }}.
            Honor dapat diajukan setelah pertemuan tersebut diabsen.</span>
    @elseif (! $p)
        <span class="label diambil">Disetujui wali</span>
        <a href="{{ route('mentor.honor.form', $k) }}" class="tombol utama">Ajukan Honor</a>
    @elseif ($p->status === 'Dikembalikan')
        <a href="{{ route('mentor.honor.form', $k) }}" class="tombol utama">Ajukan Ulang</a>
        <span class="sub">Dikembalikan owner: {{ $p->alasan_pengembalian }}</span>
    @else
        <span class="label s-{{ strtolower($p->status) }}">Honor {{ $p->status }}</span>
    @endif
</div>
