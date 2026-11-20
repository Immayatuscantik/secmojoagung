<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_honor', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_mentor');
            $table->unsignedInteger('id_kursus');
            $table->date('tanggal_pengajuan');
            $table->string('video', 100);
            $table->text('alasan_pengembalian')->nullable();
            $table->enum('status', ['Diajukan', 'Dikembalikan', 'Disetujui', 'Dicairkan'])->default('Diajukan');
            $table->timestamps();

            $table->foreign('id_mentor')->references('id')->on('mentor')->restrictOnDelete();
            $table->foreign('id_kursus')->references('id')->on('kursus')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_honor');
    }
};