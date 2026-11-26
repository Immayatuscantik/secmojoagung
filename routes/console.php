<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Menyiapkan data simulasi untuk demo/seminar: MENGHAPUS SEMUA DATA, lalu mengisi data dummy
// seolah-olah "hari ini" adalah tanggal yang dipilih. Contoh:
//   php artisan simulasi:siapkan 2026-12-01
//   php artisan simulasi:siapkan 2026-12-01 --lat=-7.5660 --lng=112.3420
Artisan::command('simulasi:siapkan {tanggal? : Tanggal simulasi (YYYY-MM-DD), bawaan hari ini} {--lat= : Latitude lokasi demo} {--lng= : Longitude lokasi demo} {--force : Tanpa konfirmasi}', function () {
    $isian = $this->argument('tanggal') ?? now()->toDateString();
    $tanggal = \DateTime::createFromFormat('!Y-m-d', $isian);
    if (! $tanggal || $tanggal->format('Y-m-d') !== $isian) {
        $this->error('Format tanggal salah. Contoh: php artisan simulasi:siapkan 2026-12-01');
        return 1;
    }
    $tanggal = \Illuminate\Support\Carbon::instance($tanggal);
    $lat = $this->option('lat');
    $lng = $this->option('lng');
    if (($lat !== null || $lng !== null) && (! is_numeric($lat) || ! is_numeric($lng) || abs($lat) > 90 || abs($lng) > 180)) {
        $this->error('Isi --lat dan --lng berupa angka koordinat, misalnya --lat=-7.5660 --lng=112.3420');
        return 1;
    }

    $this->warn('Semua data di database akan DIHAPUS dan diganti data simulasi tanggal ' . $tanggal->translatedFormat('l, d F Y') . '.');
    if (! $this->option('force') && ! $this->confirm('Lanjutkan?')) {
        return 0;
    }

    // Hapus gambar contoh lama (swafoto dan bukti transfer data dummy)
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    foreach (['absensi', 'bukti'] as $folder) {
        $disk->delete(array_filter($disk->files($folder), fn ($f) => str_starts_with(basename($f), 'contoh-')));
    }

    config(['sec.simulasi' => ['tanggal' => $tanggal->toDateString(), 'lat' => $lat, 'lng' => $lng]]);
    $this->call('migrate:fresh', ['--seeder' => 'Database\\Seeders\\DataDummySeeder', '--force' => true]);
    $this->info('Data simulasi tanggal ' . $tanggal->translatedFormat('d F Y') . ' siap. Export database lalu import ke hosting.');
    return 0;
})->purpose('Menyiapkan data simulasi demo pada tanggal tertentu');
