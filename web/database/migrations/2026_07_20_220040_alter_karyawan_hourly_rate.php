<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->decimal('tarif_per_jam', 10, 2)->default(10000)->after('lokasi_kerja_id');
        });
        DB::table('karyawan')->update(['tarif_per_jam' => 10000]);
        Schema::table('karyawan', fn (Blueprint $table) => $table->dropColumn('tarif_gaji_harian'));
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->decimal('tarif_gaji_harian', 10, 2)->default(80000);
        });
        DB::table('karyawan')->update(['tarif_gaji_harian' => DB::raw('tarif_per_jam * 8')]);
        Schema::table('karyawan', fn (Blueprint $table) => $table->dropColumn('tarif_per_jam'));
    }
};
