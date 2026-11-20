{{-- Isian pendaftaran kursus: bidang, hari, jam, dan tanggal mulai.
     Dipakai wali (Daftar Kursus) dan owner/admin (Tambah Murid, Tambah Kursus).
     $bidang = hasil PendaftaranKursus::pilihanBidang(), $hari = PendaftaranKursus::HARI --}}
<div class="isian">
    <label>Kursus</label>
    <select name="id_bidang" id="pilih-bidang">
        <option value="">-- Pilih kursus --</option>
        @foreach ($bidang as $b)
            <option value="{{ $b->id }}" data-paket="{{ $b->paket }}" data-tingkat="{{ $b->tingkat }}"
                    @selected(old('id_bidang') == $b->id)>
                {{ $b->nama_bidang }} ({{ str_replace(',', ', ', $b->tingkat) }})
            </option>
        @endforeach
    </select>
    @error('id_bidang') <small class="galat">{{ $message }}</small> @enderror
</div>

<label>Hari belajar</label>
<p class="bantuan">Centang hari belajar setiap pekan (maksimal 5 hari). Paket berlaku 4 pekan.</p>
<div class="hari">
    @foreach ($hari as $no => $nama)
        <label>
            <input type="checkbox" name="hari[]" value="{{ $no }}" class="cek-hari" @checked(in_array($no, old('hari', [])))>
            {{ $nama }}
        </label>
    @endforeach
</div>
@error('hari') <small class="galat">{{ $message }}</small> @enderror
<div id="info-paket" class="info-paket">Belum ada hari yang dipilih.</div>

<div class="grid-3">
    <div class="isian">
        <label>Jam mulai</label>
        <input type="time" name="jam_mulai" value="{{ old('jam_mulai') }}">
        @error('jam_mulai') <small class="galat">{{ $message }}</small> @enderror
    </div>
    <div class="isian">
        <label>Jam selesai</label>
        <input type="time" name="jam_selesai" value="{{ old('jam_selesai') }}">
        @error('jam_selesai') <small class="galat">{{ $message }}</small> @enderror
    </div>
    <div class="isian">
        <label>Tanggal mulai belajar</label>
        <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}">
        @error('tanggal_mulai') <small class="galat">{{ $message }}</small> @enderror
    </div>
</div>

@push('script')
<script>
    (function () {
        const pilihBidang = document.getElementById('pilih-bidang');
        const info = document.getElementById('info-paket');
        const pilihJenjang = document.querySelector('select[name="jenjang"]'); // ada di form Tambah Murid

        // Menampilkan jumlah pertemuan secara langsung saat hari dicentang
        function perbaruiInfo() {
            const jumlahHari = document.querySelectorAll('.cek-hari:checked').length;
            const opsi = pilihBidang.options[pilihBidang.selectedIndex];
            const paket = (opsi && opsi.dataset.paket) ? opsi.dataset.paket.split(',').map(Number) : [];
            const pertemuan = jumlahHari * 4;

            info.classList.remove('peringatan');
            if (jumlahHari === 0) {
                info.textContent = 'Belum ada hari yang dipilih.';
                return;
            }
            info.textContent = jumlahHari + '× per pekan × 4 pekan = ' + pertemuan + ' pertemuan.';
            if (jumlahHari > 5) {
                info.textContent += ' Maksimal 5 hari per pekan.';
                info.classList.add('peringatan');
            } else if (paket.length && !paket.includes(pertemuan)) {
                info.textContent += ' Kursus ini hanya tersedia untuk ' + paket.join(', ') + ' pertemuan ('
                    + paket.map(p => p / 4).join(', ') + ' hari per pekan).';
                info.classList.add('peringatan');
            }
        }

        // Di form Tambah Murid: sembunyikan kursus yang tidak tersedia untuk jenjang yang dipilih
        function saringBidang() {
            if (!pilihJenjang) return;
            const tingkat = pilihJenjang.value.split(' ')[0];
            Array.from(pilihBidang.options).forEach(o => {
                if (!o.value) return;
                const cocok = !tingkat || o.dataset.tingkat.split(',').includes(tingkat);
                o.hidden = !cocok;
                if (!cocok && o.selected) pilihBidang.value = '';
            });
        }

        pilihBidang.addEventListener('change', perbaruiInfo);
        document.querySelectorAll('.cek-hari').forEach(c => c.addEventListener('change', perbaruiInfo));
        if (pilihJenjang) pilihJenjang.addEventListener('change', () => { saringBidang(); perbaruiInfo(); });
        saringBidang();
        perbaruiInfo();
    })();
</script>
@endpush