<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penggajian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $table->tinyInteger('periode_bulan');
            $table->smallInteger('periode_tahun');
            $table->integer('total_hadir')->default(0);
            $table->decimal('tarif_harian', 10, 2);
            $table->decimal('total_honorarium', 12, 2)->default(0);
            $table->decimal('total_bonus', 12, 2)->default(0);
            $table->decimal('total_gaji', 12, 2)->default(0);
            $table->enum('status_bayar', ['belum_dibayar', 'sudah_dibayar'])->default('belum_dibayar');
            $table->date('tanggal_bayar')->nullable();
            $table->timestamps();
            $table->unique(['karyawan_id', 'periode_bulan', 'periode_tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penggajian');
    }
};
