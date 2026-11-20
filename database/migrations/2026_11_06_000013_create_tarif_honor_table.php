<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tarif honor mentor dari bimbel (bukan biaya les yang dibayar wali)
        Schema::create('tarif_honor', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_bidang');
            $table->enum('tingkat', ['TK', 'SD', 'SMP', 'SMA', 'Semua']);
            $table->enum('jenis', ['paket', 'per_pertemuan']);
            $table->integer('jumlah_pertemuan')->nullable(); // diisi hanya untuk jenis paket
            $table->unsignedInteger('nominal');
            $table->timestamps();

            $table->foreign('id_bidang')->references('id')->on('bidang')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif_honor');
    }
};