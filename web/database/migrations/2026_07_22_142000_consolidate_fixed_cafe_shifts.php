<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shifts') || ! DB::table('shifts')->exists()) {
            return;
        }

        $morningId = DB::table('shifts')->where('nama', 'Pagi')->value('id');
        if (! $morningId) {
            $morningId = DB::table('shifts')->insertGetId([
                'nama' => 'Pagi', 'jam_mulai' => '08:00:00', 'jam_selesai' => '16:00:00',
                'toleransi_menit' => 15, 'is_aktif' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $nightId = DB::table('shifts')->where('nama', 'Malam')->value('id');
        if (! $nightId) {
            $nightId = DB::table('shifts')->insertGetId([
                'nama' => 'Malam', 'jam_mulai' => '16:00:00', 'jam_selesai' => '00:00:00',
                'toleransi_menit' => 15, 'is_aktif' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('shifts')->where('id', $morningId)->update(['nama' => 'Pagi', 'jam_mulai' => '08:00:00', 'jam_selesai' => '16:00:00', 'toleransi_menit' => 15, 'is_aktif' => true]);
        DB::table('shifts')->where('id', $nightId)->update(['nama' => 'Malam', 'jam_mulai' => '16:00:00', 'jam_selesai' => '00:00:00', 'toleransi_menit' => 15, 'is_aktif' => true]);

        $otherShifts = DB::table('shifts')->whereNotIn('id', [$morningId, $nightId])->get(['id', 'jam_mulai']);
        foreach ($otherShifts as $shift) {
            $targetId = $shift->jam_mulai >= '16:00:00' || $shift->jam_mulai < '08:00:00' ? $nightId : $morningId;
            DB::table('jadwal_kerja')->where('shift_id', $shift->id)->update(['shift_id' => $targetId]);
        }

        DB::table('shifts')->whereNotIn('id', [$morningId, $nightId])->delete();
    }

    public function down(): void
    {
        // Shift tambahan tidak dibuat ulang karena operasional kafe hanya dua shift tetap.
    }
};
