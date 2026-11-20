<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Jadwal pengganti: pertemuan yang dipindah ke tanggal lain
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal', function (Blueprint $table) {
            $table->date('tanggal_asal')->nullable()->after('jam_selesai');           // diisi jika jadwal dipindah
            $table->string('keterangan', 255)->nullable()->after('tanggal_asal');   // alasan penggantian
        });
    }

    public function down(): void
    {
        Schema::table('jadwal', function (Blueprint $table) {
            $table->dropColumn(['tanggal_asal', 'keterangan']);
        });
    }
};
