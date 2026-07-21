<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->unsignedSmallInteger('durasi_menit')->default(480);
            $table->unsignedSmallInteger('toleransi_menit')->default(15);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
