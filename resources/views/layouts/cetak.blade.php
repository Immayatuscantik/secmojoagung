{{-- Tata letak halaman cetak formal (kop surat + tabel + tanda tangan).
     Dipakai oleh owner/mentor/cetak dan owner/murid/cetak. Halaman langsung membuka dialog cetak browser. --}}
@php $kop = config('sec'); $kontak = collect([$kop['telepon'] ? 'Telp/WA ' . $kop['telepon'] : null, $kop['email'] ? 'Email ' . $kop['email'] : null])->filter()->implode(' · '); @endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul') - SEC Mojoagung</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <style>
        @page { size: A4 portrait; margin: 15mm 15mm 15mm 20mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; color: #000; font-family: "Times New Roman", Times, serif; font-size: 11pt; }

        .bilah-cetak { position: sticky; top: 0; display: flex; justify-content: space-between; align-items: center; gap: 12px;
                       padding: 10px 20px; background: #1f2937; color: #fff; font-family: system-ui, sans-serif; font-size: 14px; }
        .bilah-cetak button { padding: 8px 16px; border: 0; border-radius: 8px; font: inherit; font-weight: 600; cursor: pointer; }
        .bilah-cetak .utama { background: #fff; color: #111827; }
        .bilah-cetak .biasa { background: transparent; color: #fff; border: 1px solid #9ca3af; }

        .kertas { width: 210mm; min-height: 297mm; margin: 20px auto; padding: 15mm 15mm 15mm 20mm; background: #fff; box-shadow: 0 2px 12px rgba(0, 0, 0, .15); }

        .kop { display: flex; align-items: center; gap: 16px; padding-bottom: 8px; border-bottom: 3px double #000; }
        .kop img { width: 70px; height: 70px; object-fit: contain; }
        .kop div { flex: 1; text-align: center; }
        .kop h1 { margin: 0; font-size: 16pt; letter-spacing: .5px; }
        .kop p { margin: 1px 0; font-size: 10pt; }
        .kop .tagline { font-style: italic; font-size: 11pt; }

        .judul-laporan { text-align: center; margin: 14px 0 12px; }
        .judul-laporan h2 { margin: 0; font-size: 13pt; text-decoration: underline; }
        .judul-laporan p { margin: 4px 0 0; }

        .tabel-formal { width: 100%; border-collapse: collapse; }
        .tabel-formal th, .tabel-formal td { border: 1px solid #000; padding: 3px 5px; vertical-align: top; text-align: left; }
        .tabel-formal thead th { background: #e5e5e5; text-align: center; vertical-align: middle; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .tabel-formal thead { display: table-header-group; }
        .tabel-formal tbody { break-inside: avoid; }
        .tabel-formal .no { width: 28px; text-align: center; }
        .tabel-formal .kosong { text-align: center; font-style: italic; }
        .keterangan-jumlah { margin: 6px 0 0; }

        .tanda-tangan { width: 260px; margin: 28px 0 0 auto; text-align: center; break-inside: avoid; }
        .tanda-tangan p { margin: 2px 0; }
        .tanda-tangan .ruang-ttd { height: 60px; }

        @media print {
            body { background: #fff; }
            .bilah-cetak { display: none; }
            .kertas { width: auto; min-height: 0; margin: 0; padding: 0 2px; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="bilah-cetak">
        <span>Pratinjau cetak: @yield('judul')</span>
        <div>
            <button type="button" class="biasa" id="tombol-tutup">Tutup</button>
            <button type="button" class="utama" id="tombol-cetak">Cetak</button>
        </div>
    </div>

    <main class="kertas">
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
            <h2>@yield('judul-cetak')</h2>
            <p>Per tanggal {{ now()->translatedFormat('d F Y') }}</p>
        </div>

        @yield('isi')

        @if (request()->boolean('ttd', true))
            <div class="tanda-tangan">
                <p>{{ $kop['kota'] }}, {{ now()->translatedFormat('d F Y') }}</p>
                <p>Mengetahui,</p>
                <p>{{ $kop['penanda'] }}</p>
                <div class="ruang-ttd"></div>
                <p>(..............................................)</p>
            </div>
        @endif
    </main>

    <script>
        document.getElementById('tombol-cetak').addEventListener('click', () => window.print());
        document.getElementById('tombol-tutup').addEventListener('click', () => window.close());
        // Dialog cetak browser langsung terbuka saat halaman selesai dimuat
        window.addEventListener('load', () => setTimeout(() => window.print(), 300));
    </script>
</body>
</html>
