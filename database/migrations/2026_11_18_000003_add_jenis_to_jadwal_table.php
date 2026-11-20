<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Jenis pertemuan: rutin (bagian paket) atau tambahan (kompensasi tanpa biaya, tidak menambah honor)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal', function (Blueprint $table) {
            $table->enum('jenis', ['rutin', 'tambahan'])->default('rutin')->after('pertemuan_ke');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });
    }
};
