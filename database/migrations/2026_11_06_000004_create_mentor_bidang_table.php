<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keahlian mentor: bidang yang dikuasai beserta tingkat yang sanggup diajar.
        // Satu baris = satu pasangan bidang + tingkat, misalnya (Sempoa, SD) dan (Sempoa, SMP).
        Schema::create('mentor_bidang', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_mentor');
            $table->unsignedInteger('id_bidang');
            $table->enum('tingkat', ['TK', 'SD', 'SMP', 'SMA']);
            $table->timestamps();

            $table->unique(['id_mentor', 'id_bidang', 'tingkat']);
            $table->foreign('id_mentor')->references('id')->on('mentor')->cascadeOnDelete();
            $table->foreign('id_bidang')->references('id')->on('bidang')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_bidang');
    }
};