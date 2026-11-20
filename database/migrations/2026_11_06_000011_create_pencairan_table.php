<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pencairan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_pengajuan')->unique();
            $table->date('tanggal_pencairan');
            $table->unsignedInteger('nominal');
            $table->string('bukti_transfer', 100);
            $table->timestamps();

            $table->foreign('id_pengajuan')->references('id')->on('pengajuan_honor')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pencairan');
    }
};