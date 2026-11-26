<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BidangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\KursusController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LogAktivitasController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\MuridController;
use App\Http\Controllers\MuridSayaController;
use App\Http\Controllers\PengajuanHonorController;
use App\Http\Controllers\PengajuanJadwalController;
use App\Http\Controllers\PertemuanTambahanController;
use App\Http\Controllers\PortofolioController;
use App\Http\Controllers\ProfilMentorController;
use App\Http\Controllers\TanggapanVerifikasiController;
use App\Http\Controllers\TarifHonorController;
use App\Http\Controllers\VerifikasiHonorController;
use App\Http\Controllers\WaliController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
    Route::get('/daftar', [AuthController::class, 'showDaftar'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'daftar'])->name('daftar.proses');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')->name('logout');

// Halaman pengelola (owner dan admin)
Route::middleware(['auth', 'peran:owner,admin'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/absensi/{absensi}', [DashboardController::class, 'show'])->name('absensi.show');

    Route::get('/mentor/cetak', [MentorController::class, 'cetak'])->name('mentor.cetak');   // Cetak data mentor
    Route::resource('mentor', MentorController::class);

    Route::get('/bidang', [BidangController::class, 'index'])->name('bidang.index');
    Route::post('/bidang', [BidangController::class, 'store'])->name('bidang.store');
    Route::put('/bidang/{bidang}', [BidangController::class, 'update'])->name('bidang.update');
    Route::delete('/bidang/{bidang}', [BidangController::class, 'destroy'])->name('bidang.destroy');

    Route::get('/tarif', [TarifHonorController::class, 'index'])->name('tarif.index');
    Route::post('/tarif', [TarifHonorController::class, 'store'])->name('tarif.store');
    Route::put('/tarif/{tarif}', [TarifHonorController::class, 'update'])->name('tarif.update');
    Route::delete('/tarif/{tarif}', [TarifHonorController::class, 'destroy'])->name('tarif.destroy');

    Route::get('/murid/cetak', [MuridController::class, 'cetak'])->name('murid.cetak');      // Cetak data murid
    Route::resource('murid', MuridController::class);
    Route::get('/murid/{murid}/kursus/tambah', [KursusController::class, 'create'])->name('kursus.create');
    Route::post('/murid/{murid}/kursus', [KursusController::class, 'store'])->name('kursus.store');
    Route::put('/kursus/{kursus}/verifikasi', [KursusController::class, 'verifikasi'])->name('kursus.verifikasi');
    Route::put('/kursus/{kursus}/konfirmasi', [KursusController::class, 'konfirmasi'])->name('kursus.konfirmasi');
    Route::delete('/kursus/{kursus}', [KursusController::class, 'destroy'])->name('kursus.destroy');
    Route::put('/jadwal/{jadwal}/ganti', [JadwalController::class, 'ganti'])->name('jadwal.ganti');
    Route::post('/kursus/{kursus}/tambahan', [PertemuanTambahanController::class, 'store'])->name('tambahan.store');
    Route::delete('/jadwal/{jadwal}/tambahan', [PertemuanTambahanController::class, 'destroy'])->name('tambahan.destroy');
    Route::get('/pengajuan-jadwal', [JadwalController::class, 'index'])->name('jadwal.index');
    Route::put('/pengajuan-jadwal/{pengajuan}/setujui', [JadwalController::class, 'setujui'])->name('jadwal.setujui');
    Route::put('/pengajuan-jadwal/{pengajuan}/tolak', [JadwalController::class, 'tolak'])->name('jadwal.tolak');

    Route::get('/honor', [VerifikasiHonorController::class, 'index'])->name('honor');
    Route::get('/honor/{pengajuan}', [VerifikasiHonorController::class, 'show'])->name('honor.show');
    Route::put('/honor/{pengajuan}/setujui', [VerifikasiHonorController::class, 'setujui'])->name('honor.setujui');
    Route::put('/honor/{pengajuan}/kembalikan', [VerifikasiHonorController::class, 'kembalikan'])->name('honor.kembalikan');
    Route::post('/honor/{pengajuan}/cairkan', [VerifikasiHonorController::class, 'cairkan'])->name('honor.cairkan');

    Route::get('/laporan', [LaporanController::class, 'detail'])->name('laporan');           // Laporan Detail
    Route::get('/laporan/ringkas', [LaporanController::class, 'ringkas'])->name('laporan.ringkas');

    // Khusus owner
    Route::middleware('peran:owner')->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
        Route::post('/admin', [AdminController::class, 'store'])->name('admin.store');
        Route::put('/admin/{admin}', [AdminController::class, 'update'])->name('admin.update');
        Route::delete('/admin/{admin}', [AdminController::class, 'destroy'])->name('admin.destroy');

        Route::get('/aktivitas', [LogAktivitasController::class, 'index'])->name('aktivitas');
    });
});

// Halaman mentor
Route::middleware(['auth', 'peran:mentor'])->prefix('mentor')->name('mentor.')->group(function () {
    Route::get('/job', [JobController::class, 'index'])->name('job');
    Route::post('/job/{kursus}/ambil', [JobController::class, 'ambil'])->name('job.ambil');

    Route::get('/murid', [MuridSayaController::class, 'index'])->name('murid');
    Route::get('/murid/{kursus}', [MuridSayaController::class, 'show'])->name('murid.show');

    Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi');
    Route::get('/absensi/{jadwal}', [AbsensiController::class, 'create'])->name('absensi.form');
    Route::post('/absensi/{jadwal}', [AbsensiController::class, 'store'])->name('absensi.store');
    Route::put('/absensi/{absensi}/catatan', [AbsensiController::class, 'ubahCatatan'])->name('absensi.catatan');

    Route::post('/jadwal/{jadwal}/ganti', [PengajuanJadwalController::class, 'store'])->name('jadwal.ajukan');
    Route::delete('/pengajuan-jadwal/{pengajuan}', [PengajuanJadwalController::class, 'destroy'])->name('jadwal.tarik');

    Route::get('/honor', [PengajuanHonorController::class, 'index'])->name('honor');
    Route::get('/honor/{kursus}/ajukan', [PengajuanHonorController::class, 'create'])->name('honor.form');
    Route::post('/honor/{kursus}', [PengajuanHonorController::class, 'store'])->name('honor.store');

    Route::get('/profil', [ProfilMentorController::class, 'index'])->name('profil');
    Route::get('/portofolio', [PortofolioController::class, 'index'])->name('portofolio');
});

// Diskusi verifikasi pertemuan (wali, mentor, owner/admin)
Route::post('/kursus/{kursus}/tanggapan', [TanggapanVerifikasiController::class, 'store'])
    ->middleware(['auth', 'peran:owner,admin,mentor,wali'])->name('tanggapan.store');

// Halaman wali murid
Route::middleware(['auth', 'peran:wali'])->prefix('wali')->name('wali.')->group(function () {
    Route::get('/anak', [WaliController::class, 'index'])->name('anak');
    Route::get('/anak/tambah', [WaliController::class, 'create'])->name('anak.create');
    Route::post('/anak', [WaliController::class, 'store'])->name('anak.store');
    Route::get('/anak/{murid}', [WaliController::class, 'show'])->name('anak.show');
    Route::get('/anak/{murid}/ubah', [WaliController::class, 'edit'])->name('anak.edit');
    Route::put('/anak/{murid}', [WaliController::class, 'update'])->name('anak.update');

    Route::get('/anak/{murid}/kursus', [WaliController::class, 'createKursus'])->name('kursus.create');
    Route::post('/anak/{murid}/kursus', [WaliController::class, 'storeKursus'])->name('kursus.store');
    Route::delete('/kursus/{kursus}', [WaliController::class, 'batalKursus'])->name('kursus.batal');
    Route::put('/kursus/{kursus}/verifikasi', [WaliController::class, 'verifikasi'])->name('kursus.verifikasi');
});
