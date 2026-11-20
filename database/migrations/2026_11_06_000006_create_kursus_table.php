<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kursus yang diambil murid; inilah yang tampil sebagai "job" bagi mentor
        Schema::create('kursus', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_murid');
            $table->unsignedInteger('id_bidang');
            $table->unsignedInteger('id_mentor')->nullable();
            $table->integer('jumlah_pertemuan');
            $table->unsignedInteger('honor'); // honor mentor untuk satu paket, dihitung dari tarif_honor
            $table->enum('status', ['menunggu', 'tersedia', 'diambil'])->default('menunggu');
            $table->enum('verifikasi_wali', ['belum', 'disetujui', 'belum_sesuai'])->default('belum');
            $table->text('alasan_wali')->nullable();
            $table->dateTime('tanggal_verifikasi')->nullable();
            $table->timestamps();

            $table->foreign('id_murid')->references('id')->on('murid')->cascadeOnDelete();
            $table->foreign('id_bidang')->references('id')->on('bidang')->restrictOnDelete();
            $table->foreign('id_mentor')->references('id')->on('mentor')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kursus');
    }
};