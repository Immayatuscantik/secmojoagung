<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Riwayat perubahan catatan materi; created_at = waktu catatan diedit
        Schema::create('riwayat_catatan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_absensi');
            $table->text('catatan_lama');
            $table->text('catatan_baru');
            $table->timestamps();

            $table->foreign('id_absensi')->references('id')->on('absensi')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_catatan');
    }
};