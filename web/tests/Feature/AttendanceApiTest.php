<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Izin;
use App\Models\JadwalKerja;
use App\Models\Karyawan;
use App\Models\LokasiKerja;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_returns_server_metadata_and_action_flags_without_schedule(): void
    {
        $location = LokasiKerja::create(['nama_lokasi' => 'Test', 'latitude' => -6.2, 'longitude' => 106.8, 'radius_meter' => 100, 'is_aktif' => true]);
        $user = User::create(['name' => 'Pegawai', 'username' => 'pegawai', 'email' => 'pegawai@test.local', 'password' => 'password', 'role' => 'karyawan', 'status' => 'aktif']);
        Karyawan::create(['user_id' => $user->id, 'nama_lengkap' => 'Pegawai', 'jabatan' => 'Barista', 'lokasi_kerja_id' => $location->id, 'tarif_per_jam' => 10000, 'tgl_bergabung' => '2026-01-01', 'status' => 'aktif']);
        Sanctum::actingAs($user);

        $this->getJson('/api/attendance/today')->assertOk()->assertJsonPath('actions.can_clock_in', false)->assertJsonStructure(['record', 'schedule', 'server_time', 'timezone', 'actions']);
    }

    public function test_night_shift_can_clock_out_on_the_next_calendar_day(): void
    {
        [$user, $employee, $location] = $this->employee();
        $shift = Shift::create(['nama' => 'Malam', 'jam_mulai' => '20:00', 'jam_selesai' => '04:00', 'toleransi_menit' => 15, 'is_aktif' => true]);
        $schedule = JadwalKerja::create(['karyawan_id' => $employee->id, 'shift_id' => $shift->id, 'lokasi_kerja_id' => $location->id, 'tanggal_operasional' => '2026-07-20']);
        Sanctum::actingAs($user);

        $this->travelTo('2026-07-20 20:00:00');
        $clockInToken = $this->proof($user, $employee, 'clock_in', scheduleId: $schedule->id, similarity: 0.91);
        $this->postJson('/api/attendance/clock-in', ['latitude' => -6.2, 'longitude' => 106.8, 'face_verification_token' => $clockInToken])->assertCreated()
            ->assertJsonPath('data.session_status', 'sedang_bekerja')
            ->assertJsonPath('data.schedule.shift.nama', 'Malam');

        $this->travelTo('2026-07-21 04:00:00');
        $attendance = Absensi::where('jadwal_kerja_id', $schedule->id)->firstOrFail();
        $clockOutToken = $this->proof($user, $employee, 'clock_out', attendanceId: $attendance->id, similarity: 0.93);
        $this->postJson('/api/attendance/clock-out', ['latitude' => -6.2, 'longitude' => 106.8, 'face_verification_token' => $clockOutToken])->assertOk()
            ->assertJsonPath('data.session_status', 'selesai')
            ->assertJsonPath('data.paid_minutes', 480)
            ->assertJsonPath('data.estimated_salary', 80000);

        $this->assertTrue(Absensi::where('jadwal_kerja_id', $schedule->id)->whereDate('tanggal', '2026-07-20')->where('paid_minutes', 480)->exists());
    }

    public function test_clock_in_requires_a_one_time_face_verification_proof(): void
    {
        [$user, $employee, $location] = $this->employee();
        $shift = Shift::create(['nama' => 'Pagi', 'jam_mulai' => '08:00', 'jam_selesai' => '16:00', 'toleransi_menit' => 15, 'is_aktif' => true]);
        $schedule = JadwalKerja::create(['karyawan_id' => $employee->id, 'shift_id' => $shift->id, 'lokasi_kerja_id' => $location->id, 'tanggal_operasional' => '2026-07-20']);
        Sanctum::actingAs($user);
        $this->travelTo('2026-07-20 08:00:00');

        $this->postJson('/api/attendance/clock-in', ['latitude' => -6.2, 'longitude' => 106.8])->assertUnprocessable();
        $token = $this->proof($user, $employee, 'clock_in', scheduleId: $schedule->id, similarity: 0.88);
        $this->postJson('/api/attendance/clock-in', ['latitude' => -6.2, 'longitude' => 106.8, 'face_verification_token' => $token])->assertCreated();
        $this->assertDatabaseHas('absensi', ['jadwal_kerja_id' => $schedule->id, 'face_similarity_score' => 0.88]);
        $this->assertNull(Cache::get('face-verification:'.hash('sha256', $token)));
    }

    public function test_approved_leave_blocks_today_action_and_direct_clock_in(): void
    {
        [$user, $employee, $location] = $this->employee();
        $shift = Shift::create(['nama' => 'Pagi', 'jam_mulai' => '08:00', 'jam_selesai' => '16:00', 'toleransi_menit' => 15, 'is_aktif' => true]);
        $schedule = JadwalKerja::create(['karyawan_id' => $employee->id, 'shift_id' => $shift->id, 'lokasi_kerja_id' => $location->id, 'tanggal_operasional' => '2026-07-20']);
        Izin::create(['karyawan_id' => $employee->id, 'tanggal_mulai' => '2026-07-20', 'tanggal_selesai' => '2026-07-20', 'alasan' => 'Sakit', 'status' => 'approved']);
        Sanctum::actingAs($user);
        $this->travelTo('2026-07-20 08:00:00');

        $this->getJson('/api/attendance/today')->assertOk()->assertJsonPath('actions.can_clock_in', false)->assertJsonPath('actions.blocked_reason', 'APPROVED_LEAVE');
        $token = $this->proof($user, $employee, 'clock_in', scheduleId: $schedule->id);
        $this->postJson('/api/attendance/clock-in', ['latitude' => -6.2, 'longitude' => 106.8, 'face_verification_token' => $token])->assertUnprocessable()->assertJsonPath('code', 'APPROVED_LEAVE');
    }

    public function test_mobile_correction_payload_is_accepted_for_incomplete_attendance(): void
    {
        [$user, $employee, $location] = $this->employee();
        $shift = Shift::create(['nama' => 'Pagi', 'jam_mulai' => '08:00', 'jam_selesai' => '16:00', 'toleransi_menit' => 15, 'is_aktif' => true]);
        $schedule = JadwalKerja::create(['karyawan_id' => $employee->id, 'shift_id' => $shift->id, 'lokasi_kerja_id' => $location->id, 'tanggal_operasional' => '2026-07-20']);
        $attendance = Absensi::create(['karyawan_id' => $employee->id, 'jadwal_kerja_id' => $schedule->id, 'tanggal' => '2026-07-20', 'clock_in_at' => '2026-07-20 08:00:00', 'jam_masuk' => '08:00', 'status_kehadiran' => 'tidak_lengkap']);
        Sanctum::actingAs($user);
        $this->travelTo('2026-07-21 08:00:00');

        $this->postJson('/api/attendance/corrections', [
            'attendance_id' => $attendance->id,
            'clock_out_at' => '2026-07-20T16:00:00+07:00',
            'reason' => 'Lupa absen pulang',
        ])->assertCreated()->assertJsonPath('data.alasan', 'Lupa absen pulang');
    }

    private function employee(): array
    {
        $location = LokasiKerja::create(['nama_lokasi' => 'Test', 'latitude' => -6.2, 'longitude' => 106.8, 'radius_meter' => 100, 'is_aktif' => true]);
        $user = User::create(['name' => 'Pegawai', 'username' => uniqid('pegawai'), 'email' => uniqid('pegawai').'@test.local', 'password' => 'password', 'role' => 'karyawan', 'status' => 'aktif']);
        $employee = Karyawan::create(['user_id' => $user->id, 'nama_lengkap' => 'Pegawai', 'jabatan' => 'Barista', 'lokasi_kerja_id' => $location->id, 'tarif_per_jam' => 10000, 'tgl_bergabung' => '2026-01-01', 'status' => 'aktif']);

        return [$user, $employee, $location];
    }

    private function proof(User $user, Karyawan $employee, string $action, ?int $scheduleId = null, ?int $attendanceId = null, float $similarity = 0.9): string
    {
        $token = uniqid('face-proof-', true);
        Cache::put('face-verification:'.hash('sha256', $token), [
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'access_token_id' => $user->currentAccessToken()?->id,
            'action' => $action,
            'schedule_id' => $scheduleId,
            'attendance_id' => $attendanceId,
            'similarity' => $similarity,
        ], now()->addMinutes(2));

        return $token;
    }
}
