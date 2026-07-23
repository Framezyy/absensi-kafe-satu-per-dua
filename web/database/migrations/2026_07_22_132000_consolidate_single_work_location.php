<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $primaryLocationId = DB::table('lokasi_kerja')
            ->orderByDesc(DB::raw('(SELECT COUNT(*) FROM karyawan WHERE karyawan.lokasi_kerja_id = lokasi_kerja.id)'))
            ->orderBy('id')
            ->value('id');

        if (! $primaryLocationId) {
            return;
        }

        DB::table('jadwal_kerja')->where('lokasi_kerja_id', '!=', $primaryLocationId)->update(['lokasi_kerja_id' => $primaryLocationId]);
        DB::table('karyawan')->where('lokasi_kerja_id', '!=', $primaryLocationId)->update(['lokasi_kerja_id' => $primaryLocationId]);
        DB::table('lokasi_kerja')->where('id', '!=', $primaryLocationId)->delete();
        DB::table('lokasi_kerja')->where('id', $primaryLocationId)->update(['is_aktif' => true]);
    }

    public function down(): void
    {
        // Data lokasi duplikat tidak dibuat ulang karena sistem hanya mendukung satu lokasi.
    }
};
