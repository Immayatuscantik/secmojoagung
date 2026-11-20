<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pengajuan ganti hari oleh mentor, diproses (disetujui/ditolak) oleh owner/admin
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_jadwal', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_jadwal');
            $table->unsignedInteger('id_mentor');
            $table->date('tanggal_baru');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('alasan', 255);
            $table->enum('status', ['Menunggu', 'Disetujui', 'Ditolak'])->default('Menunggu');
            $table->string('catatan_pengelola', 255)->nullable();
            $table->unsignedInteger('id_pemroses')->nullable();
            $table->dateTime('diproses_pada')->nullable();
            $table->timestamps();

            $table->foreign('id_jadwal')->references('id')->on('jadwal')->cascadeOnDelete();
            $table->foreign('id_mentor')->references('id')->on('mentor')->restrictOnDelete();
            $table->foreign('id_pemroses')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_jadwal');
    }
};
