{{-- Laporan versi cetak formal: kop surat + tabel. Tidak tampil di layar, hanya saat dicetak.
     Bagian yang dicetak dipilih lewat dialog "Cetak Laporan". Dipakai di owner/laporan.blade.php. --}}
@php
    $kop      = config('sec');
    $no       = 0;
    $daftarBagian = ['ringkasan' => 'Ringkasan', 'mentor' => 'Rekap Mentor', 'murid' => 'Rekap Murid', 'honor' => 'Rekap Honor', 'lain' => 'Informasi Lain'];
    $kontak   = collect([$kop['telepon'] ? 'Telp/WA ' . $kop['telepon'] : null, $kop['email'] ? 'Email ' . $kop['email'] : null])->filter()->implode(' · ');
@endphp

{{-- ===== Dialog pilihan bagian yang dicetak ===== --}}
<dialog id="dialog-cetak" class="dialog tanpa-cetak">
    <div class="kepala">
        <div>
            <h2>Cetak Laporan {{ $ringkas ? 'Ringkas' : 'Detail' }}</h2>
            <p class="sub">Periode {{ $judul }}. Pilih bagian yang akan dicetak.</p>
        </div>
        <button type="button" class="tombol kecil" id="tutup-cetak">Tutup</button>
    </div>
    <div class="pilihan-cetak">
        <label class="pilih-semua"><input type="checkbox" id="cetak-semua" checked> <strong>Pilih semua</strong></label>
        @foreach ($daftarBagian as $kunci => $nama)
            <label><input type="checkbox" class="pilih-bagian" value="{{ $kunci }}" checked> {{ $nama }}</label>
        @endforeach
        <label class="garis-atas"><input type="checkbox" id="cetak-ttd" checked> Sertakan kolom tanda tangan</label>
        <small class="galat tersembunyi" id="galat-cetak">Pilih minimal satu bagian.</small>
    </div>
    <div class="aksi-cetak">
        <button type="button" class="tombol kecil" id="batal-cetak">Batal</button>
        <button type="button" class="tombol kecil utama" id="mulai-cetak">Cetak</button>
    </div>
</dialog>

{{-- ===== Isi cetak formal ===== --}}
<div id="laporan-cetak" class="cetak-formal {{ $ringkas ? 'jenis-ringkas' : 'jenis-detail' }}">
    <header class="kop">
        <img src="{{ asset('images/logo.png') }}" alt="Logo SEC">
        <div>
            <h1>{{ $kop['nama'] }}</h1>
            <p class="tagline">{{ $kop['tagline'] }}</p>
            <p>{{ $kop['alamat'] }}</p>
            @if ($kontak)<p>{{ $kontak }}</p>@endif
        </div>
    </header>

    <div class="judul-laporan">
        <h2>LAPORAN {{ $ringkas ? 'RINGKAS' : 'DETAIL' }} {{ strtoupper($periode) }}</h2>
        <p>Periode: {{ $judul }} ({{ $awal->translatedFormat('d F Y') }} – {{ $akhir->translatedFormat('d F Y') }})</p>
    </div>

    {{-- ===== Ringkasan ===== --}}
    <section class="bagian-cetak" data-bagian="ringkasan">
        <h3>Ringkasan</h3>
        <table class="tabel-formal tabel-ringkasan">
            <thead><tr><th class="no">No</th><th>Keterangan</th><th class="angka">Nilai</th></tr></thead>
            <tbody>
                @php
                    $barisRingkasan = [
                        ['Pertemuan yang dijadwalkan', $ringkasan['target'] . ' pertemuan'],
                        ['Pertemuan terpenuhi', $ringkasan['terpenuhi'] . ' pertemuan (' . $pct($ringkasan['persen']) . ')'],
                        ['Pertemuan tidak diabsen', $ringkasan['tidak_absen'] . ' pertemuan'],
                        ['Pertemuan belum dilaksanakan', $ringkasan['belum'] . ' pertemuan'],
                        ['Pertemuan diganti ke hari lain', $ringkasan['ganti'] . ' pertemuan'],
                        ['Absensi tercatat', $ringkasan['absensi'] . ' absensi'],
                        ['Absensi tepat lokasi (≤ 50 m)', $ringkasan['valid'] . ' absensi (' . $pct($ringkasan['persen_valid']) . ')'],
                        ['Absensi perlu ditinjau', $ringkasan['tinjau'] . ' absensi'],
                        ['Murid aktif / mentor aktif', $ringkasan['murid_aktif'] . ' murid / ' . $ringkasan['mentor_aktif'] . ' mentor'],
                        ['Murid baru / kursus baru', $ringkasan['murid_baru'] . ' murid / ' . $ringkasan['kursus_baru'] . ' kursus'],
                        ['Kursus selesai', $ringkasan['kursus_selesai'] . ' kursus'],
                        ['Total honor kursus selesai', $rp($ringkasan['hak_honor'])],
                        ['Honor sudah dicairkan', $rp($ringkasan['hak_cair'])],
                        ['Honor belum dicairkan', $rp($ringkasan['hak_belum'])],
                        ['Dana honor dicairkan pada periode ini', $rp($ringkasan['cair_periode']) . ' (' . $ringkasan['cair_jumlah'] . ' kali)'],
                        ['Mentor yang tidak mengajar', $mentorTidakAktif->count() . ' mentor'],
                        ['Kursus yang masih mencari mentor', $jobTersedia->count() . ' kursus'],
                    ];
                    if ($terbaik) {
                        $barisRingkasan[] = ['Ketercapaian tertinggi', $terbaik->mentor->user->nama . ' (' . $pct($terbaik->persen) . ')'];
                        if ($aktifMentor->count() > 1) {
                            $barisRingkasan[] = ['Ketercapaian terendah', $terendah->mentor->user->nama . ' (' . $pct($terendah->persen) . ')'];
                        }
                    }
                @endphp
                @foreach ($barisRingkasan as $i => [$ket, $nilai])
                    <tr><td class="no">{{ $i + 1 }}</td><td>{{ $ket }}</td><td class="angka">{{ $nilai }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </section>

    {{-- ===== Rekap Mentor ===== --}}
    <section class="bagian-cetak" data-bagian="mentor">
        <h3>Rekap Mentor</h3>
        @if ($ringkas)
            <table class="tabel-formal">
                <thead><tr><th class="no">No</th><th>Nama Mentor</th><th class="angka">Target</th><th class="angka">Terpenuhi</th><th class="angka">Ketercapaian</th></tr></thead>
                <tbody>
                    @foreach ($rekapMentor as $i => $r)
                        <tr>
                            <td class="no">{{ $loop->iteration }}</td>
                            <td>{{ $r->mentor->user->nama }}</td>
                            <td class="angka">{{ $r->target }}</td>
                            <td class="angka">{{ $r->terpenuhi }}</td>
                            <td class="angka">{{ $r->target > 0 ? $pct($r->persen) : 'Tidak mengajar' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr><th colspan="2">Total</th><th class="angka">{{ $ringkasan['target'] }}</th><th class="angka">{{ $ringkasan['terpenuhi'] }}</th><th class="angka">{{ $pct($ringkasan['persen']) }}</th></tr></tfoot>
            </table>
        @else
            <p class="sub-judul">a. Rekap pertemuan dan honor per mentor</p>
            <table class="tabel-formal">
                <thead>
                    <tr><th class="no">No</th><th>Nama Mentor</th><th class="angka">Murid</th><th class="angka">Target</th><th class="angka">Terpenuhi</th>
                        <th class="angka">Tidak Absen</th><th class="angka">Diganti</th><th class="angka">Ketercapaian</th><th class="angka">Valid / Ditinjau</th>
                        <th class="angka">Hak Honor</th><th class="angka">Sudah Cair</th><th class="angka">Belum Cair</th></tr>
                </thead>
                <tbody>
                    @foreach ($rekapMentor as $r)
                        <tr>
                            <td class="no">{{ $loop->iteration }}</td>
                            <td>{{ $r->mentor->user->nama }}</td>
                            <td class="angka">{{ $r->murid }}</td>
                            <td class="angka">{{ $r->target }}</td>
                            <td class="angka">{{ $r->terpenuhi }}</td>
                            <td class="angka">{{ $r->tidak_absen }}</td>
                            <td class="angka">{{ $r->ganti }}</td>
                            <td class="angka">{{ $r->target > 0 ? $pct($r->persen) : '-' }}</td>
                            <td class="angka">{{ $r->valid }} / {{ $r->tinjau }}</td>
                            <td class="angka">{{ $rp($r->hak) }}</td>
                            <td class="angka">{{ $rp($r->cair) }}</td>
                            <td class="angka">{{ $rp($r->hak - $r->cair) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><th colspan="2">Total</th><th class="angka">{{ $ringkasan['murid_aktif'] }}</th><th class="angka">{{ $ringkasan['target'] }}</th>
                        <th class="angka">{{ $ringkasan['terpenuhi'] }}</th><th class="angka">{{ $ringkasan['tidak_absen'] }}</th><th class="angka">{{ $ringkasan['ganti'] }}</th>
                        <th class="angka">{{ $pct($ringkasan['persen']) }}</th><th class="angka">{{ $rekapMentor->sum('valid') }} / {{ $rekapMentor->sum('tinjau') }}</th>
                        <th class="angka">{{ $rp($rekapMentor->sum('hak')) }}</th><th class="angka">{{ $rp($rekapMentor->sum('cair')) }}</th>
                        <th class="angka">{{ $rp($rekapMentor->sum('hak') - $rekapMentor->sum('cair')) }}</th></tr>
                </tfoot>
            </table>

            <p class="sub-judul">b. Rincian murid yang diajar setiap mentor</p>
            <table class="tabel-formal">
                <thead><tr><th class="no">No</th><th>Nama Mentor</th><th>Nama Murid</th><th>Kursus</th><th class="angka">Pertemuan Periode Ini</th><th class="angka">Progres Paket</th><th>Status Honor</th></tr></thead>
                <tbody>
                    @php $nomorRincian = 0; @endphp
                    @forelse ($aktifMentor as $r)
                        @foreach ($r->kursus as $k)
                            <tr>
                                <td class="no">{{ ++$nomorRincian }}</td>
                                <td>{{ $r->mentor->user->nama }}</td>
                                <td>{{ $k->murid->nama }} ({{ $k->murid->jenjang }})</td>
                                <td>{{ $k->bidang->nama_bidang }}</td>
                                <td class="angka">{{ $k->terpenuhi_periode }} / {{ $k->target_periode }}</td>
                                <td class="angka">{{ $k->hadir_count }} / {{ $k->jumlah_pertemuan }}</td>
                                <td>{{ $k->statusHonor()[0] }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="7" class="kosong">Tidak ada mentor yang mengajar pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </section>

    {{-- ===== Rekap Murid ===== --}}
    <section class="bagian-cetak" data-bagian="murid">
        <h3>Rekap Murid</h3>
        @if ($ringkas)
            <table class="tabel-formal">
                <thead><tr><th class="no">No</th><th>Nama Murid</th><th>Kursus</th><th class="angka">Pertemuan Periode Ini</th><th class="angka">Progres Paket</th></tr></thead>
                <tbody>
                    @forelse ($kursus as $k)
                        <tr>
                            <td class="no">{{ $loop->iteration }}</td>
                            <td>{{ $k->murid->nama }}</td>
                            <td>{{ $k->bidang->nama_bidang }}</td>
                            <td class="angka">{{ $k->terpenuhi_periode }} / {{ $k->target_periode }}</td>
                            <td class="angka">{{ $k->hadir_count }} / {{ $k->jumlah_pertemuan }} ({{ $pct($persen($k->hadir_count, $k->jumlah_pertemuan)) }})</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="kosong">Tidak ada kursus yang memiliki jadwal pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @else
            <table class="tabel-formal">
                <thead><tr><th class="no">No</th><th>Nama Murid</th><th>Jenjang</th><th>Wali</th><th>Kursus</th><th>Mentor</th>
                    <th class="angka">Pertemuan Periode Ini</th><th class="angka">Progres Paket</th><th>Verifikasi Wali</th><th>Status Honor</th></tr></thead>
                <tbody>
                    @forelse ($kursus as $k)
                        <tr>
                            <td class="no">{{ $loop->iteration }}</td>
                            <td>{{ $k->murid->nama }}</td>
                            <td>{{ $k->murid->jenjang }}</td>
                            <td>{{ $k->murid->wali->user->nama }}</td>
                            <td>{{ $k->bidang->nama_bidang }}</td>
                            <td>{{ $k->mentor?->user->nama ?? 'Belum ada mentor' }}</td>
                            <td class="angka">{{ $k->terpenuhi_periode }} / {{ $k->target_periode }}</td>
                            <td class="angka">{{ $k->hadir_count }} / {{ $k->jumlah_pertemuan }}</td>
                            <td>{{ $k->id_mentor ? $k->labelVerifikasi()[0] : '-' }}</td>
                            <td>{{ $k->id_mentor ? $k->statusHonor()[0] : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="kosong">Tidak ada kursus yang memiliki jadwal pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </section>

    {{-- ===== Rekap Honor ===== --}}
    <section class="bagian-cetak" data-bagian="honor">
        <h3>Rekap Honor</h3>
        @if ($ringkas)
            <table class="tabel-formal">
                <thead><tr><th class="no">No</th><th>Bulan</th><th class="angka">Kursus Berakhir</th><th class="angka">Total Honor</th><th class="angka">Sudah Cair</th><th class="angka">Belum Cair</th></tr></thead>
                <tbody>
                    @foreach ($honorBulanan as $h)
                        <tr>
                            <td class="no">{{ $loop->iteration }}</td>
                            <td>{{ $h->bulan }}</td>
                            <td class="angka">{{ $h->kursus }}</td>
                            <td class="angka">{{ $rp($h->total) }}</td>
                            <td class="angka">{{ $rp($h->cair) }}</td>
                            <td class="angka">{{ $rp($h->total - $h->cair) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr><th colspan="2">Total</th><th class="angka">{{ $honorBulanan->sum('kursus') }}</th><th class="angka">{{ $rp($honorBulanan->sum('total')) }}</th>
                    <th class="angka">{{ $rp($honorBulanan->sum('cair')) }}</th><th class="angka">{{ $rp($honorBulanan->sum('total') - $honorBulanan->sum('cair')) }}</th></tr></tfoot>
            </table>
        @else
            <p class="sub-judul">a. Honor per kursus yang berakhir pada periode ini</p>
            <table class="tabel-formal">
                <thead><tr><th class="no">No</th><th>Pertemuan Terakhir</th><th>Mentor</th><th>Murid</th><th>Kursus</th><th class="angka">Pertemuan</th>
                    <th class="angka">Honor</th><th>Status</th><th>Tanggal Cair</th></tr></thead>
                <tbody>
                    @forelse ($kursusBerakhir as $k)
                        <tr>
                            <td class="no">{{ $loop->iteration }}</td>
                            <td>{{ \Carbon\Carbon::parse($k->tanggal_akhir)->translatedFormat('d M Y') }}</td>
                            <td>{{ $k->mentor->user->nama }}</td>
                            <td>{{ $k->murid->nama }}</td>
                            <td>{{ $k->bidang->nama_bidang }}</td>
                            <td class="angka">{{ $k->hadir_count }} / {{ $k->jumlah_pertemuan }}</td>
                            <td class="angka">{{ $rp($k->nominal_honor) }}</td>
                            <td>{{ $k->statusHonor()[0] }}</td>
                            <td>{{ $k->pengajuanTerakhir()?->pencairan?->tanggal_pencairan?->translatedFormat('d M Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="kosong">Tidak ada kursus yang berakhir pada periode ini.</td></tr>
                    @endforelse
                </tbody>
                @if ($kursusBerakhir->isNotEmpty())
                    <tfoot>
                        <tr><th colspan="6">Total honor</th><th class="angka">{{ $rp($ringkasan['hak_honor']) }}</th><th colspan="2"></th></tr>
                        <tr><th colspan="6">Sudah cair / belum cair</th><th class="angka" colspan="3">{{ $rp($ringkasan['hak_cair']) }} / {{ $rp($ringkasan['hak_belum']) }}</th></tr>
                    </tfoot>
                @endif
            </table>

            <p class="sub-judul">b. Pencairan dana honor pada periode ini</p>
            <table class="tabel-formal">
                <thead><tr><th class="no">No</th><th>Tanggal</th><th>Mentor</th><th>Murid</th><th>Kursus</th><th class="angka">Nominal</th></tr></thead>
                <tbody>
                    @forelse ($pencairan as $c)
                        @php $p = $c->pengajuanHonor; @endphp
                        <tr>
                            <td class="no">{{ $loop->iteration }}</td>
                            <td>{{ $c->tanggal_pencairan->translatedFormat('d M Y') }}</td>
                            <td>{{ $p->mentor->user->nama }}</td>
                            <td>{{ $p->kursus->murid->nama }}</td>
                            <td>{{ $p->kursus->bidang->nama_bidang }} ({{ $p->kursus->jumlah_pertemuan }} pertemuan)</td>
                            <td class="angka">{{ $rp($c->nominal) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="kosong">Belum ada pencairan pada periode ini.</td></tr>
                    @endforelse
                </tbody>
                @if ($pencairan->isNotEmpty())
                    <tfoot><tr><th colspan="5">Total</th><th class="angka">{{ $rp($pencairan->sum('nominal')) }}</th></tr></tfoot>
                @endif
            </table>
        @endif
    </section>

    {{-- ===== Informasi Lain ===== --}}
    <section class="bagian-cetak" data-bagian="lain">
        <h3>Informasi Lain</h3>
        <p class="sub-judul">a. Mentor yang tidak mengajar</p>
        <table class="tabel-formal">
            <thead><tr><th class="no">No</th><th>Nama Mentor</th><th>Keahlian</th><th>No. HP</th><th class="angka">Job Sesuai yang Tersedia</th></tr></thead>
            <tbody>
                @forelse ($mentorTidakAktif as $r)
                    <tr>
                        <td class="no">{{ $loop->iteration }}</td>
                        <td>{{ $r->mentor->user->nama }}</td>
                        <td>{{ $r->mentor->ringkasanKeahlian() }}</td>
                        <td>{{ $r->mentor->user->no_hp }}</td>
                        <td class="angka">{{ $r->job_sesuai }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="kosong">Semua mentor mengajar pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>

        <p class="sub-judul">b. Pendaftaran kursus per bidang</p>
        <table class="tabel-formal">
            <thead><tr><th class="no">No</th><th>Bidang</th><th class="angka">Jumlah</th><th class="angka">Persentase</th></tr></thead>
            <tbody>
                @forelse ($pendaftaranBidang as $nama => $jumlah)
                    <tr>
                        <td class="no">{{ $loop->iteration }}</td>
                        <td>{{ $nama }}</td>
                        <td class="angka">{{ $jumlah }}</td>
                        <td class="angka">{{ $pct($persen($jumlah, $pendaftaranBidang->sum())) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="kosong">Tidak ada pendaftaran kursus pada periode ini.</td></tr>
                @endforelse
            </tbody>
            @if ($pendaftaranBidang->isNotEmpty())
                <tfoot><tr><th colspan="2">Total</th><th class="angka">{{ $pendaftaranBidang->sum() }}</th><th class="angka">100%</th></tr></tfoot>
            @endif
        </table>

        <p class="sub-judul">c. Murid yang belum mendapat mentor</p>
        <table class="tabel-formal">
            <thead><tr><th class="no">No</th><th>Nama Murid</th><th>Kursus</th><th>Wali</th><th>Rencana Mulai</th><th class="angka">Lama Menunggu</th></tr></thead>
            <tbody>
                @forelse ($jobTersedia as $k)
                    <tr>
                        <td class="no">{{ $loop->iteration }}</td>
                        <td>{{ $k->murid->nama }} ({{ $k->murid->jenjang }})</td>
                        <td>{{ $k->bidang->nama_bidang }}</td>
                        <td>{{ $k->murid->wali->user->nama }}</td>
                        <td>{{ $k->jadwal->sortBy('pertemuan_ke')->first()?->tanggal?->translatedFormat('d M Y') ?? '-' }}</td>
                        <td class="angka">{{ (int) $k->created_at->diffInDays(now()) }} hari</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="kosong">Semua kursus sudah memiliki mentor.</td></tr>
                @endforelse
            </tbody>
        </table>

        <p class="sub-judul">d. Absensi yang perlu ditinjau</p>
        <table class="tabel-formal">
            <thead><tr><th class="no">No</th><th>Waktu Absen</th><th>Mentor</th><th>Murid</th><th class="angka">Jarak</th></tr></thead>
            <tbody>
                @forelse ($absensiTinjau as $a)
                    <tr>
                        <td class="no">{{ $loop->iteration }}</td>
                        <td>{{ $a->waktu_absen->translatedFormat('d M Y, H.i') }}</td>
                        <td>{{ $a->jadwal->kursus->mentor?->user->nama ?? '-' }}</td>
                        <td>{{ $a->jadwal->kursus->murid->nama }}</td>
                        <td class="angka">{{ number_format($a->jarak, 0, ',', '.') }} m</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="kosong">Tidak ada absensi yang perlu ditinjau pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="tanda-tangan" id="blok-ttd">
        <p>{{ $kop['kota'] }}, {{ now()->translatedFormat('d F Y') }}</p>
        <p>Mengetahui,</p>
        <p>{{ $kop['penanda'] }}</p>
        <div class="ruang-ttd"></div>
        <p>(..............................................)</p>
    </div>
</div>

<style>
    /* ---------- Dialog pilihan cetak (tampil di layar) ---------- */
    .pilihan-cetak { display: flex; flex-direction: column; gap: 10px; margin: 16px 0; font-size: 14px; }
    .pilihan-cetak label { display: flex; align-items: center; gap: 8px; cursor: pointer; }
    .pilihan-cetak .pilih-semua { padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; }
    .pilihan-cetak .garis-atas { padding-top: 8px; border-top: 1px solid #e5e7eb; }
    .aksi-cetak { display: flex; justify-content: flex-end; gap: 8px; }

    /* ---------- Laporan formal: disembunyikan di layar ---------- */
    .cetak-formal { display: none; }

    @page { size: A4 portrait; margin: 15mm 15mm 15mm 20mm; }
    @page lebar { size: A4 landscape; margin: 12mm 12mm 12mm 15mm; }

    @media print {
        body > *:not(.cetak-formal) { display: none !important; }
        body { background: #fff !important; margin: 0 !important; display: block !important; }
        .cetak-formal, .cetak-formal h1, .cetak-formal h2, .cetak-formal h3, .cetak-formal p { color: #000 !important; }
        .cetak-formal { display: block !important; padding: 0 2px; box-sizing: border-box; overflow: visible; font-family: "Times New Roman", Times, serif; font-size: 11pt; }
        .cetak-formal.jenis-detail { page: lebar; font-size: 10pt; }

        .cetak-formal .kop { display: flex; align-items: center; gap: 16px; padding-bottom: 8px; border-bottom: 3px double #000; }
        .cetak-formal .kop img { width: 70px; height: 70px; object-fit: contain; }
        .cetak-formal .kop div { flex: 1; text-align: center; }
        .cetak-formal .kop h1 { margin: 0; font-size: 16pt; letter-spacing: .5px; }
        .cetak-formal .kop p { margin: 1px 0; font-size: 10pt; }
        .cetak-formal .kop .tagline { font-style: italic; font-size: 11pt; }

        .cetak-formal .judul-laporan { text-align: center; margin: 14px 0 10px; }
        .cetak-formal .judul-laporan h2 { margin: 0; font-size: 13pt; text-decoration: underline; }
        .cetak-formal .judul-laporan p { margin: 4px 0 0; font-size: 11pt; }

        .cetak-formal { counter-reset: bagian; }
        .cetak-formal .bagian-cetak { margin-top: 14px; }
        .cetak-formal .bagian-cetak.tidak-dicetak { display: none; }
        .cetak-formal .bagian-cetak h3 { counter-increment: bagian; margin: 0 0 6px; font-size: 12pt; break-after: avoid; }
        .cetak-formal .bagian-cetak h3::before { content: counter(bagian, upper-alpha) ". "; }
        .cetak-formal .sub-judul { margin: 10px 0 4px; font-weight: bold; break-after: avoid; }

        .tabel-formal { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .tabel-formal th, .tabel-formal td { border: 1px solid #000; padding: 3px 5px; vertical-align: top; text-align: left; }
        .tabel-formal thead th { background: #e5e5e5 !important; text-align: center; vertical-align: middle; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .tabel-formal thead { display: table-header-group; }
        .tabel-formal tfoot th { background: #f2f2f2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .tabel-formal tr { break-inside: avoid; }
        .tabel-formal .no { width: 28px; text-align: center; }
        .tabel-formal .angka { text-align: right; white-space: nowrap; }
        .tabel-formal thead .angka { text-align: center; white-space: normal; }
        .tabel-formal .kosong { text-align: center; font-style: italic; }
        .tabel-ringkasan { width: 80%; max-width: 170mm; }
        .jenis-detail .tabel-formal { font-size: 9pt; }
        .jenis-detail .tabel-formal th, .jenis-detail .tabel-formal td { padding: 2px 4px; }
        .jenis-detail .tabel-formal .angka { white-space: normal; }

        .cetak-formal .tanda-tangan { width: 260px; margin: 28px 0 0 auto; text-align: center; break-inside: avoid; }
        .cetak-formal .tanda-tangan p { margin: 2px 0; }
        .cetak-formal .tanda-tangan .ruang-ttd { height: 60px; }
        .cetak-formal .tanda-tangan.tidak-dicetak { display: none; }
    }
</style>

<script>
    (function () {
        const laporan = document.getElementById('laporan-cetak');
        const dialog  = document.getElementById('dialog-cetak');
        if (!laporan || !dialog) return;
        // Laporan dipindah menjadi anak langsung <body> agar saat dicetak hanya bagian ini yang tampil
        document.body.appendChild(laporan);

        const pilihan  = dialog.querySelectorAll('.pilih-bagian');
        const semua    = document.getElementById('cetak-semua');
        const ttd      = document.getElementById('cetak-ttd');
        const galat    = document.getElementById('galat-cetak');

        function aturSemua() {
            const jumlah = [...pilihan].filter(c => c.checked).length;
            semua.checked = jumlah === pilihan.length;
            semua.indeterminate = jumlah > 0 && jumlah < pilihan.length;
        }
        semua.addEventListener('change', () => { pilihan.forEach(c => c.checked = semua.checked); galat.classList.add('tersembunyi'); });
        pilihan.forEach(c => c.addEventListener('change', () => { aturSemua(); galat.classList.add('tersembunyi'); }));

        document.getElementById('tombol-cetak').addEventListener('click', () => dialog.showModal());
        document.getElementById('tutup-cetak').addEventListener('click', () => dialog.close());
        document.getElementById('batal-cetak').addEventListener('click', () => dialog.close());

        document.getElementById('mulai-cetak').addEventListener('click', () => {
            const dipilih = [...pilihan].filter(c => c.checked).map(c => c.value);
            if (dipilih.length === 0) { galat.classList.remove('tersembunyi'); return; }
            laporan.querySelectorAll('.bagian-cetak').forEach(b => b.classList.toggle('tidak-dicetak', !dipilih.includes(b.dataset.bagian)));
            document.getElementById('blok-ttd').classList.toggle('tidak-dicetak', !ttd.checked);
            dialog.close();
            setTimeout(() => window.print(), 100);
        });
    })();
</script>
