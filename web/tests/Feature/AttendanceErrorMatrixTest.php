<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\AttendanceCorrection;
use App\Services\DailyScheduleMaterializer;
use App\Services\FaceVerificationProofService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceErrorMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_in_error_matrix(): void
    {
        [$user, $employee, $location] = $this->createEmployee();
        $defaultShift = $employee->defaultShift;
        $employee->update(['default_shift_id' => null]);
        Sanctum::actingAs($user);
        $this->travelTo('2026-07-20 08:00');
        $base = ['latitude' => -6.2, 'longitude' => 106.8, 'face_verification_token' => 'proof'];
        $this->postJson('/api/attendance/clock-in', $base)->assertUnprocessable()->assertJsonPath('code', 'NO_ACTIVE_SCHEDULE');

        $employee->update(['default_shift_id' => $defaultShift->id]);
        $schedule = app(DailyScheduleMaterializer::class)->materializeForDate(now(), $employee->id)->first();
        $mockProof = app(FaceVerificationProofService::class)->issue($user, 'clock_in', 0.9, $schedule)['token'];
        $this->postJson('/api/attendance/clock-in', array_merge($base, ['face_verification_token' => $mockProof, 'is_mocked' => true]))->assertJsonPath('code', 'MOCK_LOCATION');
        $outsideProof = app(FaceVerificationProofService::class)->issue($user, 'clock_in', 0.9, $schedule)['token'];
        $this->postJson('/api/attendance/clock-in', array_merge($base, ['face_verification_token' => $outsideProof, 'latitude' => 0, 'longitude' => 0]))->assertJsonPath('code', 'OUTSIDE_GEOFENCE');
        $location->update(['is_aktif' => false]);
        $inactiveProof = app(FaceVerificationProofService::class)->issue($user, 'clock_in', 0.9, $schedule)['token'];
        $this->postJson('/api/attendance/clock-in', array_merge($base, ['face_verification_token' => $inactiveProof]))->assertJsonPath('code', 'LOCATION_INACTIVE');
        $this->postJson('/api/attendance/clock-in', array_merge($base, ['face_verification_token' => $inactiveProof]))->assertJsonPath('code', 'FACE_VERIFICATION_REQUIRED');
    }

    public function test_clock_out_without_open_session_is_rejected(): void
    {
        [$user] = $this->createEmployee();
        Sanctum::actingAs($user);
        $this->postJson('/api/attendance/clock-out', ['latitude' => -6.2, 'longitude' => 106.8, 'face_verification_token' => 'x'])
            ->assertUnprocessable()->assertJsonPath('code', 'NO_OPEN_SESSION');
    }

    public function test_history_and_correction_ownership_and_invalid_time(): void
    {
        [$owner, $employee] = $this->createEmployee();
        [$other, $otherEmployee] = $this->createEmployee();
        $attendance = Absensi::create(['karyawan_id' => $employee->id, 'tanggal' => '2026-07-20', 'clock_in_at' => '2026-07-20 08:00', 'jam_masuk' => '08:00', 'status_kehadiran' => 'tidak_lengkap']);
        AttendanceCorrection::create(['absensi_id' => $attendance->id, 'karyawan_id' => $employee->id, 'requested_clock_out_at' => '2026-07-20 16:00', 'alasan' => 'Lupa', 'status' => 'pending']);
        Sanctum::actingAs($other);

        $this->getJson('/api/attendance/history?month=2026-07')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/attendance/corrections')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson('/api/attendance/corrections', ['attendance_id' => $attendance->id, 'clock_out_at' => '2026-07-20 16:00', 'reason' => 'Bukan milik saya'])->assertJsonPath('code', 'NOT_CORRECTABLE');

        Sanctum::actingAs($owner);
        AttendanceCorrection::query()->delete();
        $this->postJson('/api/attendance/corrections', ['attendance_id' => $attendance->id, 'clock_out_at' => '2026-07-20 07:59', 'reason' => 'Waktu salah'])->assertJsonPath('code', 'INVALID_CLOCK_OUT');
    }
}
