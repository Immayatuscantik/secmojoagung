{{-- Daftar kursus seorang murid beserta jadwalnya.
     $peran = 'pengelola' (owner/admin) atau 'wali'. --}}
@forelse ($murid->kursus as $k)
    @php
        $k->jadwal->each->setRelation('kursus', $k);
        [$teksStatus, $kelasStatus] = $k->labelStatus();
        $hadir = $k->jadwal->filter(fn ($j) => ! $j->tambahan() && $j->absensi)->count();
        $ganti = $k->jadwal->whereNotNull('tanggal_asal')->count();
        $tambahan = $k->jadwal->where('jenis', 'tambahan');
        $bisaTambah = $peran === 'pengelola' && $k->status === 'diambil' && $k->id_mentor && ! $k->pengajuanTerakhir();
        $lengkap = $k->pertemuanLengkap();
        $awal  = $k->jadwal->min('tanggal');
        $akhir = $k->jadwal->max('tanggal');
    @endphp

    <section class="panel">
        <div class="kepala">
            <div>
                <h2>{{ $k->bidang->nama_bidang }} <span class="label {{ $kelasStatus }}">{{ $teksStatus }}</span></h2>
                <p class="sub">Didaftarkan {{ $k->created_at->translatedFormat('d F Y') }}</p>
            </div>
            <div class="baris-tombol tanpa-jarak">
                @if ($peran === 'pengelola')
                    @if ($k->status === 'menunggu')
                        <form method="POST" action="{{ route('owner.kursus.konfirmasi', $k) }}"
                              onsubmit="return confirm('Konfirmasi kursus ini? Kursus akan tampil di Daftar Job mentor.')">
                            @csrf @method('PUT')
                            <button type="submit" class="tombol kecil utama">Konfirmasi</button>
                        </form>
                    @endif
                    @if ($bisaTambah)
                        <button type="button" class="tombol kecil buka-dialog" data-dialog="tambah-pertemuan-{{ $k->id }}">Tambah Pertemuan</button>
                    @endif
                    @if ($hadir === 0)
                        <form method="POST" action="{{ route('owner.kursus.destroy', $k) }}"
                              onsubmit="return confirm('Hapus kursus ini beserta jadwalnya?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="tombol kecil bahaya">Hapus</button>
                        </form>
                    @endif
                @elseif ($k->status === 'menunggu')
                    <form method="POST" action="{{ route('wali.kursus.batal', $k) }}"
                          onsubmit="return confirm('Batalkan pendaftaran kursus ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="tombol kecil bahaya">Batalkan</button>
                    </form>
                @endif
            </div>
        </div>

        <dl class="info-lokasi jarak-atas-kiri">
            <dt>Mentor</dt>   <dd>{{ $k->mentor?->user->nama ?? 'Belum ada mentor' }}</dd>
            <dt>Jadwal</dt>   <dd>{{ $k->hariBelajar() }}, {{ $k->jamBelajar() }}</dd>
            <dt>Periode</dt>  <dd>{{ $awal?->translatedFormat('d M Y') }} – {{ $akhir?->translatedFormat('d M Y') }}</dd>
            <dt>Paket</dt>    <dd>{{ $k->jumlah_pertemuan }} pertemuan</dd>
            @if ($peran === 'pengelola')
                <dt>Honor mentor</dt>
                <dd>{{ \App\Support\Rupiah::format($k->honor) }}</dd>
            @endif
            <dt>Progres</dt>
            <dd>
                {{ $hadir }} / {{ $k->jumlah_pertemuan }} pertemuan
                @if ($ganti) <span class="label s-diajukan">{{ $ganti }} pertemuan pengganti</span> @endif
                @if ($tambahan->isNotEmpty()) <span class="label tersedia">+{{ $tambahan->count() }} pertemuan tambahan (tanpa biaya)</span> @endif
                <progress class="progres" max="{{ $k->jumlah_pertemuan }}" value="{{ $hadir }}"></progress>
            </dd>
            @if ($k->status === 'diambil')
                @php [$teksV, $kelasV] = $k->labelVerifikasi(); @endphp
                <dt>Verifikasi wali</dt>
                <dd>
                    <span class="label {{ $kelasV }}">{{ $teksV }}</span>
                    @if ($k->tanggal_verifikasi)
                        <span class="sub">{{ $k->tanggal_verifikasi->translatedFormat('d F Y, H.i') }}</span>
                    @endif
                    @if ($k->verifikasi_wali === 'belum_sesuai')
                        <br><span class="sub">Alasan: {{ $k->alasan_wali }}</span>
                        <br><a href="#diskusi-{{ $k->id }}" class="sub">Lihat diskusi dan tanggapan mentor</a>
                    @endif
                </dd>
            @endif
        </dl>

        @include('partials.diskusi-verifikasi', ['k' => $k])

        {{-- Verifikasi oleh admin/owner atas nama wali (misalnya wali tidak memakai sistem) --}}
        @if ($peran === 'pengelola' && $k->status === 'diambil' && $lengkap && $k->verifikasi_wali !== 'disetujui')
            <div class="kotak-verifikasi">
                <h3>Verifikasi atas nama wali</h3>
                <p>Seluruh pertemuan sudah terpenuhi tetapi belum disetujui wali. Jika wali sudah mengonfirmasi
                    melalui telepon/WhatsApp, admin dapat memverifikasi atas nama wali. Tindakan ini tercatat di Riwayat Aktivitas.</p>
                <form method="POST" action="{{ route('owner.kursus.verifikasi', $k) }}" class="formulir"
                      onsubmit="return confirm('Verifikasi pertemuan atas nama wali?')">
                    @csrf @method('PUT')
                    <label>Keterangan konfirmasi</label>
                    <input type="text" name="keterangan_verifikasi" maxlength="255"
                           placeholder="Contoh: dikonfirmasi Ibu Wali via WhatsApp, 11 November 2026">
                    @error('keterangan_verifikasi') <small class="galat">{{ $message }}</small> @enderror
                    <div class="baris-tombol">
                        <button type="submit" class="tombol utama">Verifikasi atas Nama Wali</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Verifikasi wali: muncul setelah seluruh pertemuan terpenuhi --}}
        @if ($peran === 'wali' && $k->status === 'diambil' && $lengkap && $k->verifikasi_wali !== 'disetujui')
            <div class="kotak-verifikasi">
                <h3>Verifikasi pertemuan</h3>
                @if ($sisa = $k->tambahanBelumSelesai()->first())
                    <p class="jarak-bawah"><strong>Pertemuan tambahan tanpa biaya</strong> dijadwalkan pada
                        {{ $sisa->tanggal->translatedFormat('l, d F Y') }} pukul {{ str_replace(':', '.', substr($sisa->jam_mulai, 0, 5)) }}.
                        Sebaiknya Anda menyetujui setelah pertemuan tersebut dilaksanakan.</p>
                @endif
                @if ($k->diskusiTerbuka())
                    <p>Anda menyatakan pertemuan belum sesuai. Baca tanggapan mentor pada Diskusi verifikasi di atas.
                        Jika permasalahan sudah selesai, klik <strong>Setujui</strong> agar mentor dapat mengajukan honor.</p>
                @else
                    <p>Seluruh {{ $k->jumlah_pertemuan }} pertemuan telah tercatat. Periksa jadwal dan catatan materi di bawah,
                        lalu nyatakan apakah pembelajaran sudah sesuai. Persetujuan Anda diperlukan agar mentor dapat mengajukan honor.</p>
                @endif
                <form method="POST" action="{{ route('wali.kursus.verifikasi', $k) }}" class="formulir">
                    @csrf @method('PUT')
                    <input type="hidden" name="id_kursus_verifikasi" value="{{ $k->id }}">
                    <label>{{ $k->diskusiTerbuka() ? 'Catatan (opsional jika Setujui, wajib jika tetap Belum Sesuai)' : 'Alasan (wajib diisi jika memilih "Belum Sesuai")' }}</label>
                    <textarea name="alasan_wali" rows="2" maxlength="500"
                              placeholder="Contoh: pertemuan ke-3 mentor tidak datang">{{ (string) old('id_kursus_verifikasi') === (string) $k->id ? old('alasan_wali') : '' }}</textarea>
                    @if ((string) old('id_kursus_verifikasi') === (string) $k->id)
                        @error('alasan_wali') <small class="galat">{{ $message }}</small> @enderror
                    @endif
                    <div class="baris-tombol">
                        <button type="submit" name="keputusan" value="disetujui" class="tombol utama"
                                onclick="return confirm('Setujui bahwa seluruh pertemuan sudah sesuai?')">Setujui</button>
                        <button type="submit" name="keputusan" value="belum_sesuai" class="tombol bahaya">Belum Sesuai</button>
                    </div>
                </form>
            </div>
        @endif

        <details class="jarak-atas">
            <summary>Lihat jadwal dan perkembangan belajar</summary>
            <table class="tabel jarak-atas-kiri">
                <thead>
                    <tr><th>Ke</th><th>Tanggal</th><th>Jam</th><th>Kehadiran</th><th>Catatan materi</th>
                        @if ($peran === 'pengelola' && $k->status === 'diambil')<th class="tanpa-cetak">Aksi</th>@endif</tr>
                </thead>
                <tbody>
                    @foreach ($k->jadwal as $j)
                        @php
                            $batas = \Carbon\Carbon::parse($j->tanggal->toDateString() . ' ' . $j->jam_mulai)
                                ->addMinutes(\App\Http\Controllers\AbsensiController::TUTUP_SESUDAH);
                        @endphp
                        <tr>
                            <td>{{ $j->pertemuan_ke }}@if ($j->tambahan())<br><span class="label tersedia">Tambahan</span>@endif</td>
                            <td>
                                {{ $j->tanggal->translatedFormat('D, d M Y') }}
                                @if ($j->tanggal_asal)
                                    <br><span class="sub">Pengganti dari {{ $j->tanggal_asal->translatedFormat('d M Y') }}</span>
                                @endif
                            </td>
                            <td>{{ substr($j->jam_mulai, 0, 5) }}–{{ substr($j->jam_selesai, 0, 5) }}</td>
                            <td>
                                @if ($j->absensi)
                                    <span class="label {{ $j->absensi->status === 'Valid' ? 'diambil' : 'tersedia' }}">Hadir</span>
                                    @if ($peran === 'pengelola')
                                        <a href="{{ route('owner.absensi.show', $j->absensi) }}" class="tombol kecil">Detail</a>
                                    @endif
                                @elseif ($k->status === 'diambil' && now()->gt($batas))
                                    <span class="label tidak-hadir">Tidak absen</span>
                                @else
                                    <span class="sub">Belum</span>
                                @endif
                            </td>
                            <td>
                                {{ $j->absensi?->catatan_materi ?? '-' }}
                                @if ($j->keterangan && ($j->tambahan() || ! $j->absensi))
                                    <br><span class="sub">{{ $j->tambahan() ? 'Alasan tambahan' : 'Alasan ganti' }}: {{ $j->keterangan }}</span>
                                @endif
                            </td>
                            @if ($peran === 'pengelola' && $k->status === 'diambil')
                                <td class="aksi tanpa-cetak">
                                    @if ($j->bisaDiubah())
                                        <button type="button" class="tombol kecil buka-dialog" data-dialog="ubah-jadwal-{{ $j->id }}">Ganti Jadwal</button>
                                        @if ($j->pengajuanMenunggu())
                                            <a href="{{ route('owner.jadwal.index') }}" class="label tersedia">Diajukan mentor</a>
                                        @endif
                                        @if ($j->tambahan())
                                            <form method="POST" action="{{ route('owner.tambahan.destroy', $j) }}"
                                                  onsubmit="return confirm('Hapus pertemuan tambahan ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="tombol kecil bahaya">Hapus</button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </details>

        {{-- Pop-up ganti jadwal (owner/admin) --}}
        @if ($peran === 'pengelola' && $k->status === 'diambil')
            @foreach ($k->jadwal->filter(fn ($j) => $j->bisaDiubah()) as $j)
                @include('partials.dialog-ubah-jadwal', ['j' => $j, 'k' => $k])
            @endforeach
        @endif
        @if ($bisaTambah)
            @include('partials.dialog-tambah-pertemuan', ['k' => $k])
        @endif
    </section>
@empty
    <section class="panel">
        <p class="kosong-besar">Belum ada kursus yang didaftarkan.</p>
    </section>
@endforelse

@if ($peran === 'pengelola')
    @include('partials.dialog-skrip')
@endif
