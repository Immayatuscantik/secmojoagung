<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Mencatat akun yang melakukan verifikasi pertemuan:
    // akun wali sendiri, atau admin/owner yang memverifikasi atas nama wali.
    public function up(): void
    {
        Schema::table('kursus', function (Blueprint $table) {
            $table->unsignedInteger('id_verifikator')->nullable()->after('tanggal_verifikasi');
            $table->foreign('id_verifikator')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kursus', function (Blueprint $table) {
            $table->dropForeign(['id_verifikator']);
            $table->dropColumn('id_verifikator');
        });
    }
};