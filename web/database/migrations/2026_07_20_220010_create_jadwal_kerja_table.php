<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_kerja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->restrictOnDelete();
            $table->foreignId('lokasi_kerja_id')->constrained('lokasi_kerja')->restrictOnDelete();
            $table->date('tanggal_operasional');
            $table->timestamps();
            $table->unique(['karyawan_id', 'tanggal_operasional']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_kerja');
    }
};
