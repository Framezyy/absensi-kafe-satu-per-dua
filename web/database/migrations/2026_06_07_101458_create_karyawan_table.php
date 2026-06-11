<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('karyawan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nik', 20)->unique();
            $table->string('nama_lengkap', 100);
            $table->string('jabatan', 100);
            $table->foreignId('lokasi_kerja_id')->nullable()->constrained('lokasi_kerja')->nullOnDelete();
            $table->decimal('tarif_gaji_harian', 10, 2)->default(0);
            $table->date('tgl_bergabung');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('karyawan'); }
};