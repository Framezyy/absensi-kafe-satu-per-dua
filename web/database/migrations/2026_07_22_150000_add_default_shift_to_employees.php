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
            $table->foreignId('default_shift_id')->nullable()->after('lokasi_kerja_id')->constrained('shifts')->restrictOnDelete();
        });

        $morningId = DB::table('shifts')->where('nama', 'Pagi')->value('id');
        DB::table('karyawan')->orderBy('id')->each(function ($employee) use ($morningId) {
            $latestShiftId = DB::table('jadwal_kerja')->where('karyawan_id', $employee->id)->orderByDesc('tanggal_operasional')->value('shift_id');
            DB::table('karyawan')->where('id', $employee->id)->update(['default_shift_id' => $latestShiftId ?: $morningId]);
        });
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_shift_id');
        });
    }
};
