{{-- Dialog filter cetak untuk Data Mentor dan Data Murid.
     Variabel: $judulCetak, $ruteCetak, $namaStatus, $opsiStatus (nilai => label), $statusTerpilih (array),
     $labelTanpa, $cari, $bidang, $tingkat, $daftarBidang, $daftarTingkat --}}
<dialog id="dialog-cetak" class="dialog">
    <div class="kepala">
        <div>
            <h2>Cetak {{ $judulCetak }}</h2>
            <p class="sub">Pilih data yang akan dicetak. Hasil cetak dibuka di tab baru.</p>
        </div>
        <button type="button" class="tombol kecil" id="tutup-cetak">Tutup</button>
    </div>

    <form method="GET" action="{{ route($ruteCetak) }}" target="_blank" class="form-cetak jarak-atas" id="form-cetak">
        @if ($cari)<input type="hidden" name="cari" value="{{ $cari }}">@endif

        <div class="baris-cetak">
            <label>Kursus / bidang
                <select name="bidang">
                    <option value="">Semua kursus</option>
                    @foreach ($daftarBidang as $b)
                        <option value="{{ $b->id }}" @selected($bidang === $b->id)>{{ $b->nama_bidang }}</option>
                    @endforeach
                </select>
            </label>
            <label>Tingkat
                <select name="tingkat">
                    <option value="">Semua tingkat</option>
                    @foreach ($daftarTingkat as $t)
                        <option value="{{ $t }}" @selected($tingkat === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <fieldset class="pilihan-cetak">
            <legend>Status kursus yang dicetak</legend>
            @foreach ($opsiStatus as $nilai => $label)
                <label><input type="checkbox" class="pilih-status" name="{{ $namaStatus }}[]" value="{{ $nilai }}" @checked(in_array($nilai, $statusTerpilih))> {{ $label }}</label>
            @endforeach
            <small class="galat tersembunyi" id="galat-cetak">Pilih minimal satu status kursus.</small>
        </fieldset>

        <fieldset class="pilihan-cetak">
            <legend>Lainnya</legend>
            <input type="hidden" name="tanpa_kursus" value="0">
            <label><input type="checkbox" name="tanpa_kursus" value="1" checked> {{ $labelTanpa }}</label>
            <input type="hidden" name="ttd" value="0">
            <label><input type="checkbox" name="ttd" value="1" checked> Sertakan kolom tanda tangan</label>
        </fieldset>

        <div class="aksi-cetak">
            <button type="button" class="tombol kecil" id="batal-cetak">Batal</button>
            <button type="submit" class="tombol kecil utama">Cetak</button>
        </div>
    </form>
</dialog>

<style>
    /* Dialog filter cetak */
    #form-cetak .baris-cetak { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    #form-cetak .baris-cetak label { display: flex; flex-direction: column; gap: 6px; margin: 0; font-size: 13px; font-weight: 600; }
    #form-cetak .baris-cetak select { width: 100%; }
    #form-cetak .pilihan-cetak { display: flex; flex-direction: column; align-items: flex-start; gap: 10px; margin: 14px 0 0; padding: 10px 14px 12px; border: 1px solid #e5e7eb; border-radius: 10px; }
    #form-cetak .pilihan-cetak legend { padding: 0 4px; font-size: 13px; font-weight: 600; }
    #form-cetak .pilihan-cetak label { display: flex; flex-direction: row; align-items: center; justify-content: flex-start; gap: 10px; width: auto; margin: 0; font-size: 14px; font-weight: 400; text-align: left; cursor: pointer; }
    #form-cetak .pilihan-cetak input[type='checkbox'] { flex: none; width: 16px; height: 16px; margin: 0; padding: 0; }
    #form-cetak .aksi-cetak { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
    @media (max-width: 520px) { #form-cetak .baris-cetak { grid-template-columns: 1fr; } }

    /* Tombol Cetak Data dan Tambah tetap di pojok kanan atas */
    .kepala > div:first-child { flex: 1 1 320px; min-width: 0; }
    .kumpulan-tombol { display: flex; flex: none; flex-wrap: wrap; gap: 8px; margin-left: auto; }
</style>

<script>
    (function () {
        const dialog = document.getElementById('dialog-cetak');
        const galat  = document.getElementById('galat-cetak');
        const status = dialog.querySelectorAll('.pilih-status');

        document.getElementById('buka-cetak').addEventListener('click', () => dialog.showModal());
        document.getElementById('tutup-cetak').addEventListener('click', () => dialog.close());
        document.getElementById('batal-cetak').addEventListener('click', () => dialog.close());
        status.forEach(c => c.addEventListener('change', () => galat.classList.add('tersembunyi')));

        document.getElementById('form-cetak').addEventListener('submit', e => {
            if (![...status].some(c => c.checked)) { e.preventDefault(); galat.classList.remove('tersembunyi'); return; }
            setTimeout(() => dialog.close(), 100);
        });
    })();
</script>
