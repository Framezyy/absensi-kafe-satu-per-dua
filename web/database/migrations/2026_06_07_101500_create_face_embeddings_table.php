<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('face_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->unique()->constrained('karyawan')->cascadeOnDelete();
            $table->json('embedding_vector')->nullable();
            $table->string('foto_referensi_path', 255)->nullable();
            $table->date('tgl_registrasi')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('face_embeddings'); }
};