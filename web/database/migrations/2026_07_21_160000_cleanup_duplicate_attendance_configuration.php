<?php

use App\Services\AttendanceCalculationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('karyawan')->update(['tarif_per_jam' => config('payroll.hourly_rate', 10000)]);
        DB::table('penggajian')->update(['tarif_per_jam' => config('payroll.hourly_rate', 10000)]);
        DB::table('attendance_corrections')->where('status', 'disetujui')->update(['status' => 'approved']);
        DB::table('attendance_corrections')->where('status', 'ditolak')->update(['status' => 'rejected']);

        $calculation = app(AttendanceCalculationService::class);
        DB::table('absensi')
            ->join('jadwal_kerja', 'jadwal_kerja.id', '=', 'absensi.jadwal_kerja_id')
            ->join('shifts', 'shifts.id', '=', 'jadwal_kerja.shift_id')
            ->where('absensi.status_kehadiran', 'selesai')
            ->whereNotNull('absensi.clock_in_at')
            ->whereNotNull('absensi.clock_out_at')
            ->where('absensi.paid_minutes', 0)
            ->select('absensi.id', 'absensi.clock_in_at', 'absensi.clock_out_at', 'jadwal_kerja.tanggal_operasional', 'shifts.jam_mulai', 'shifts.jam_selesai', 'shifts.toleransi_menit')
            ->orderBy('absensi.id')
            ->each(function ($row) use ($calculation) {
                [$start, $end] = $calculation->shiftPeriod($row->tanggal_operasional, $row->jam_mulai, $row->jam_selesai);
                $metrics = $calculation->calculate(now()->parse($row->clock_in_at), now()->parse($row->clock_out_at), $start, $end, $row->toleransi_menit);
                DB::table('absensi')->where('id', $row->id)->update($metrics);
            });

        Schema::table('lokasi_kerja', function (Blueprint $table) {
            $table->dropColumn(['jam_masuk_standar', 'toleransi_menit']);
        });
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('durasi_menit');
        });
    }

    public function down(): void
    {
        Schema::table('lokasi_kerja', function (Blueprint $table) {
            $table->time('jam_masuk_standar')->default('08:00:00');
            $table->unsignedSmallInteger('toleransi_menit')->default(15);
        });
        Schema::table('shifts', function (Blueprint $table) {
            $table->unsignedSmallInteger('durasi_menit')->default(480);
        });
        DB::table('attendance_corrections')->where('status', 'approved')->update(['status' => 'disetujui']);
        DB::table('attendance_corrections')->where('status', 'rejected')->update(['status' => 'ditolak']);
    }
};
