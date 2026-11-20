<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Diskusi verifikasi pertemuan: keberatan wali, tanggapan mentor, dan penengahan owner/admin
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tanggapan_verifikasi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_kursus');
            $table->unsignedInteger('id_user');
            $table->enum('jenis', ['keberatan', 'tanggapan', 'persetujuan'])->default('tanggapan');
            $table->text('isi');
            $table->timestamps();

            $table->foreign('id_kursus')->references('id')->on('kursus')->cascadeOnDelete();
            $table->foreign('id_user')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanggapan_verifikasi');
    }
};
