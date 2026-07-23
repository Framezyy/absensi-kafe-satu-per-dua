<?php

namespace Tests\Unit;

use App\Models\Absensi;
use App\Models\Izin;
use App\Models\JadwalKerja;
use App\Services\ApprovedLeaveService;
use App\Services\AttendanceOverviewService;
use App\Services\DailyScheduleMaterializer;
use App\Services\FaceVerificationProofService;
use App\Services\GeofenceService;
use App\Services\ScheduleResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DomainServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_geofence_known_distance_and_inclusive_boundary(): void
    {
        $distance = GeofenceService::haversineDistance(0, 0, 0, 1);
        $this->assertEqualsWithDelta(111194.9, $distance, 1);
        $this->assertTrue(GeofenceService::isInsideRadius(0, 0, 0, 0, 0));
        $this->assertTrue(GeofenceService::isInsideRadius(0, 1, 0, 0, (int) ceil($distance)));
        $this->assertFalse(GeofenceService::isInsideRadius(0, 1, 0, 0, (int) floor($distance) - 1));
    }

    public function test_schedule_resolver_handles_overnight_windows_and_selection(): void
    {
        $night = $this->createShift(['nama' => 'Malam', 'jam_mulai' => '20:00', 'jam_selesai' => '04:00']);
        [, $employee] = $this->createEmployee([], ['shift' => $night]);
        $resolver = app(ScheduleResolver::class);

        $schedule = $resolver->forClockIn($employee->id, Carbon::parse('2026-07-21 03:59'));
        $this->assertSame('2026-07-20', $schedule->tanggal_operasional->format('Y-m-d'));
        $this->assertNull($resolver->forClockIn($employee->id, Carbon::parse('2026-07-21 04:01')));
        $this->assertNull($resolver->forClockIn($employee->id, Carbon::parse('2026-07-20 17:59')));
    }

    public function test_daily_materializer_is_idempotent_and_preserves_attended_history(): void
    {
        $morning = $this->createShift(['nama' => 'Pagi']);
        $night = $this->createShift(['nama' => 'Malam', 'jam_mulai' => '16:00', 'jam_selesai' => '00:00']);
        [, $employee] = $this->createEmployee([], ['shift' => $morning]);
        $materializer = app(DailyScheduleMaterializer::class);
        $materializer->materializeForDate(Carbon::parse('2026-07-20'), $employee->id);
        $schedule = JadwalKerja::where('karyawan_id', $employee->id)->whereDate('tanggal_operasional', '2026-07-20')->firstOrFail();
        Absensi::create(['karyawan_id' => $employee->id, 'jadwal_kerja_id' => $schedule->id, 'tanggal' => '2026-07-20', 'clock_in_at' => '2026-07-20 08:00:00', 'jam_masuk' => '08:00', 'status_kehadiran' => 'berjalan']);
        $employee->update(['default_shift_id' => $night->id]);
        $materializer->applyDefaultShift($employee->fresh(), Carbon::parse('2026-07-20'));

        $this->assertSame($morning->id, $schedule->fresh()->shift_id);
        $this->assertSame(1, JadwalKerja::where('karyawan_id', $employee->id)->whereDate('tanggal_operasional', '2026-07-20')->count());
        $this->assertSame($night->id, JadwalKerja::where('karyawan_id', $employee->id)->whereDate('tanggal_operasional', '2026-07-21')->value('shift_id'));
    }

    public function test_overlap_across_adjacent_overnight_dates_respects_one_schedule_per_day(): void
    {
        [, $employee, $location] = $this->createEmployee();
        $night = $this->createShift(['nama' => 'Malam', 'jam_mulai' => '20:00', 'jam_selesai' => '04:00']);
        $early = $this->createShift(['nama' => 'Dini Hari', 'jam_mulai' => '03:00', 'jam_selesai' => '10:00']);
        $day = $this->createShift(['nama' => 'Siang', 'jam_mulai' => '08:00', 'jam_selesai' => '16:00']);
        JadwalKerja::create(['karyawan_id' => $employee->id, 'shift_id' => $night->id, 'lokasi_kerja_id' => $location->id, 'tanggal_operasional' => '2026-07-20']);

        $resolver = app(ScheduleResolver::class);
        $this->assertTrue($resolver->overlapsExisting($employee->id, Carbon::parse('2026-07-21'), $early->id));
        $this->assertFalse($resolver->overlapsExisting($employee->id, Carbon::parse('2026-07-21'), $day->id));
    }

    public function test_approved_leave_covers_inclusive_range_only(): void
    {
        [, $employee] = $this->createEmployee();
        Izin::create(['karyawan_id' => $employee->id, 'tanggal_mulai' => '2026-07-20', 'tanggal_selesai' => '2026-07-22', 'alasan' => 'Sakit', 'status' => 'approved']);
        $service = app(ApprovedLeaveService::class);

        $this->assertTrue($service->covers($employee->id, Carbon::parse('2026-07-20')));
        $this->assertTrue($service->covers($employee->id, Carbon::parse('2026-07-22')));
        $this->assertFalse($service->covers($employee->id, Carbon::parse('2026-07-23')));
    }

    public function test_face_proof_is_one_time_and_bound_to_user_action_and_resource(): void
    {
        [$user, $employee, $location] = $this->createEmployee();
        [$other] = $this->createEmployee();
        Sanctum::actingAs($user);
        $schedule = JadwalKerja::create(['karyawan_id' => $employee->id, 'shift_id' => $this->createShift()->id, 'lokasi_kerja_id' => $location->id, 'tanggal_operasional' => '2026-07-20']);
        $service = app(FaceVerificationProofService::class);
        $proof = $service->issue($user, 'clock_in', 0.91, $schedule);

        $this->assertNull($service->consume($proof['token'], $other, 'clock_in', $schedule->id));
        $this->assertNull($service->consume($proof['token'], $user, 'clock_in', $schedule->id));

        $proof = $service->issue($user, 'clock_in', 0.91, $schedule);
        $this->assertSame(0.91, $service->consume($proof['token'], $user, 'clock_in', $schedule->id)['similarity']);
        $this->assertNull($service->consume($proof['token'], $user, 'clock_in', $schedule->id));
    }

    public function test_attendance_overview_status_matrix_and_summary(): void
    {
        $service = app(AttendanceOverviewService::class);
        $this->assertSame('Izin', $service->resolveStatus(null, false, true));
        $this->assertSame('Belum Absen', $service->resolveStatus(null, false, false));
        $this->assertSame('Tidak Lengkap', $service->resolveStatus('tidak_lengkap', true, false));
        $this->assertSame('Pulang Lebih Awal', $service->resolveStatus('selesai', true, false, 10));
        $this->assertSame('Selesai', $service->resolveStatus('selesai', true, false));
        $this->assertSame('Sedang Bekerja', $service->resolveStatus('berjalan', true, false));

        $rows = collect([
            (object) ['status' => 'Sedang Bekerja', 'pulang_lebih_awal' => false, 'terlambat' => true],
            (object) ['status' => 'Pulang Lebih Awal', 'pulang_lebih_awal' => true, 'terlambat' => false],
            (object) ['status' => 'Izin', 'pulang_lebih_awal' => false, 'terlambat' => false],
            (object) ['status' => 'Belum Absen', 'pulang_lebih_awal' => false, 'terlambat' => false],
        ]);
        $this->assertSame(['scheduled' => 4, 'checked_in' => 2, 'working' => 1, 'completed' => 1, 'early_leave' => 1, 'incomplete' => 0, 'late' => 1, 'leave' => 1, 'not_clocked_in' => 1], $service->summary($rows));
    }
}
