<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            $table->decimal('tarif_per_jam', 10, 2)->default(10000);
            $table->unsignedInteger('total_paid_minutes')->default(0);
            $table->unsignedInteger('total_tidak_lengkap')->default(0);
        });
        DB::table('penggajian')->update(['tarif_per_jam' => 10000]);
        Schema::table('penggajian', function (Blueprint $table) {
            $table->dropColumn(['tarif_harian', 'total_honorarium', 'total_bonus']);
        });
    }

    public function down(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            $table->decimal('tarif_harian', 10, 2)->default(80000);
            $table->decimal('total_honorarium', 12, 2)->default(0);
            $table->decimal('total_bonus', 12, 2)->default(0);
        });
        Schema::table('penggajian', fn (Blueprint $table) => $table->dropColumn(['tarif_per_jam', 'total_paid_minutes', 'total_tidak_lengkap']));
    }
};
