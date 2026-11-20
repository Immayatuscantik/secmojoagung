<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_jadwal')->unique();
            $table->dateTime('waktu_absen');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('jarak', 8, 2);
            $table->string('foto', 100);
            $table->text('catatan_materi');
            $table->enum('status', ['Valid', 'Perlu Ditinjau']);
            $table->timestamps();

            $table->foreign('id_jadwal')->references('id')->on('jadwal')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};