<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->foreignId('jadwal_kerja_id')->nullable()->after('karyawan_id')->constrained('jadwal_kerja')->nullOnDelete();
            $table->timestamp('clock_in_at')->nullable()->after('jam_pulang');
            $table->timestamp('clock_out_at')->nullable()->after('clock_in_at');
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('worked_minutes')->default(0);
            $table->unsignedInteger('paid_minutes')->default(0);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE absensi MODIFY status_kehadiran VARCHAR(30) NOT NULL DEFAULT 'hadir'");
            DB::statement('UPDATE absensi SET clock_in_at = TIMESTAMP(tanggal, jam_masuk) WHERE jam_masuk IS NOT NULL');
            DB::statement('UPDATE absensi SET clock_out_at = TIMESTAMP(DATE_ADD(tanggal, INTERVAL IF(jam_pulang < jam_masuk, 1, 0) DAY), jam_pulang) WHERE jam_pulang IS NOT NULL');
        } else {
            DB::table('absensi')->whereNotNull('jam_masuk')->orderBy('id')->each(function ($row) {
                DB::table('absensi')->where('id', $row->id)->update([
                    'clock_in_at' => $row->tanggal.' '.$row->jam_masuk,
                    'clock_out_at' => $row->jam_pulang ? date('Y-m-d H:i:s', strtotime($row->tanggal.' '.$row->jam_pulang.($row->jam_masuk && $row->jam_pulang < $row->jam_masuk ? ' +1 day' : ''))) : null,
                ]);
            });
        }

        DB::table('absensi')->whereNotNull('clock_out_at')->update(['status_kehadiran' => 'selesai']);
        DB::table('absensi')->whereNotNull('clock_in_at')->whereNull('clock_out_at')->update(['status_kehadiran' => 'tidak_lengkap']);
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropForeign(['jadwal_kerja_id']);
            $table->dropColumn(['jadwal_kerja_id', 'clock_in_at', 'clock_out_at', 'late_minutes', 'worked_minutes', 'paid_minutes']);
        });
    }
};
