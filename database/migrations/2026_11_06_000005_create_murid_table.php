<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Data pribadi murid (kursus yang diambil ada di tabel kursus)
        Schema::create('murid', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_wali');
            $table->string('nama', 50);
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->date('tanggal_lahir');
            $table->string('asal_sekolah', 100)->nullable();
            $table->string('jenjang', 20);
            $table->text('alamat');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->text('catatan_khusus')->nullable();
            $table->timestamps();

            $table->foreign('id_wali')->references('id')->on('wali_murid')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('murid');
    }
};