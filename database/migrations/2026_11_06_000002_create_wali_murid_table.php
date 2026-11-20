<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wali_murid', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_user')->unique();
            $table->enum('hubungan', ['Ayah', 'Ibu', 'Wali']);
            $table->string('pekerjaan', 50)->nullable();
            $table->text('alamat');
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wali_murid');
    }
};