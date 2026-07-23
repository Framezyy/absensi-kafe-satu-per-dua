<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\JadwalKerja;
use App\Services\ApprovedLeaveService;
use App\Services\AttendanceCalculationService;
use App\Services\FaceVerificationProofService;
use App\Services\GeofenceService;
use App\Services\ScheduleResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceCalculationService $calculation, private ScheduleResolver $schedules, private ApprovedLeaveService $leave, private FaceVerificationProofService $proofs) {}

    public function today(Request $request)
    {
        $now = now();
        $karyawan = $request->user()->karyawan;
        $open = $this->schedules->openAttendance($karyawan->id);
        $schedule = $open?->jadwalKerja ?: $this->schedules->forClockIn($karyawan->id, $now);
        $record = $open ?: ($schedule?->absensi()->first());
        $onLeave = $schedule && ! $open && $this->leave->covers($karyawan->id, $schedule->tanggal_operasional);

        return response()->json([
            'data' => $record ? $this->recordData($record) : null,
            'record' => $record ? $this->recordData($record) : null,
            'schedule' => $schedule ? $this->scheduleData($schedule) : null,
            'server_time' => $now->toIso8601String(),
            'timezone' => config('app.timezone'),
            'actions' => [
                'can_clock_in' => (bool) $schedule && ! $record?->clock_in_at && ! $onLeave,
                'can_clock_out' => (bool) $open,
                'can_request_correction' => $record?->status_kehadiran === 'tidak_lengkap',
                'blocked_reason' => $onLeave ? 'APPROVED_LEAVE' : null,
            ],
        ]);
    }

    public function clockIn(Request $request)
    {
        $validated = $request->validate(['latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180', 'face_verification_token' => 'required|string', 'is_mocked' => 'nullable|boolean']);
        $karyawan = $request->user()->karyawan;
        $now = now();
        $schedule = $this->schedules->forClockIn($karyawan->id, $now);

        if (! $schedule) {
            return $this->error('NO_ACTIVE_SCHEDULE', 'Tidak ada jadwal kerja yang dapat di-clock-in saat ini.');
        }
        if ($this->leave->covers($karyawan->id, $schedule->tanggal_operasional)) {
            return $this->error('APPROVED_LEAVE', 'Clock-in tidak diperbolehkan karena izin pada jadwal ini telah disetujui.');
        }
        $proof = $this->proofs->consume($validated['face_verification_token'], $request->user(), 'clock_in', $schedule->id);
        if (! $proof) {
            return $this->error('FACE_VERIFICATION_REQUIRED', 'Verifikasi wajah diperlukan, kedaluwarsa, atau sudah digunakan.');
        }
        if ($request->boolean('is_mocked')) {
            return $this->error('MOCK_LOCATION', 'Terdeteksi lokasi palsu (Fake GPS). Nonaktifkan aplikasi lokasi palsu untuk absen.');
        }
        if (! $schedule->lokasiKerja?->is_aktif) {
            return $this->error('LOCATION_INACTIVE', 'Lokasi jadwal tidak aktif.');
        }
        if (! GeofenceService::isInsideRadius($validated['latitude'], $validated['longitude'], $schedule->lokasiKerja->latitude, $schedule->lokasiKerja->longitude, $schedule->lokasiKerja->radius_meter)) {
            return $this->error('OUTSIDE_GEOFENCE', 'Anda di luar radius lokasi kerja.');
        }

        [$start] = $this->calculation->shiftPeriod($schedule->tanggal_operasional, $schedule->shift->jam_mulai, $schedule->shift->jam_selesai);

        return DB::transaction(function () use ($schedule, $karyawan, $now, $start, $validated, $request, $proof) {
            $existing = Absensi::where('jadwal_kerja_id', $schedule->id)->lockForUpdate()->first();
            if ($existing?->clock_in_at) {
                return $this->error('ALREADY_CLOCKED_IN', 'Jadwal ini sudah di-clock-in.');
            }

            $lateMinutes = $this->calculation->lateMinutes($now, $start, $schedule->shift->toleransi_menit);
            $absensi = Absensi::updateOrCreate(
                ['karyawan_id' => $karyawan->id, 'tanggal' => $schedule->tanggal_operasional->format('Y-m-d')],
                ['jadwal_kerja_id' => $schedule->id, 'clock_in_at' => $now, 'jam_masuk' => $now->format('H:i:s'), 'late_minutes' => $lateMinutes, 'lat_masuk' => $validated['latitude'], 'lng_masuk' => $validated['longitude'], 'status_kehadiran' => 'berjalan', 'face_verified' => true, 'face_similarity_score' => $proof['similarity'], 'is_mocked_masuk' => $request->boolean('is_mocked')]
            );

            return response()->json(['message' => 'Absen masuk berhasil.', 'server_time' => $now->toIso8601String(), 'data' => $this->recordData($absensi->fresh('jadwalKerja.shift', 'jadwalKerja.lokasiKerja'))], 201);
        });
    }

    public function clockOut(Request $request)
    {
        $validated = $request->validate(['latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180', 'face_verification_token' => 'required|string', 'is_mocked' => 'nullable|boolean']);
        $karyawan = $request->user()->karyawan;
        if ($request->boolean('is_mocked')) {
            return $this->error('MOCK_LOCATION', 'Terdeteksi lokasi palsu (Fake GPS). Nonaktifkan aplikasi lokasi palsu untuk absen.');
        }

        $absensi = $this->schedules->openAttendance($karyawan->id);
        if (! $absensi) {
            return $this->error('NO_OPEN_SESSION', 'Tidak ada sesi absensi terbuka.');
        }
        if (! $this->proofs->consume($validated['face_verification_token'], $request->user(), 'clock_out', null, $absensi->id)) {
            return $this->error('FACE_VERIFICATION_REQUIRED', 'Verifikasi wajah diperlukan, kedaluwarsa, atau sudah digunakan.');
        }
        $lokasi = $absensi->jadwalKerja?->lokasiKerja;
        if (! $lokasi) {
            return $this->error('SESSION_LOCATION_MISSING', 'Lokasi sesi absensi tidak tersedia.');
        }
        if (! GeofenceService::isInsideRadius($validated['latitude'], $validated['longitude'], $lokasi->latitude, $lokasi->longitude, $lokasi->radius_meter)) {
            return $this->error('OUTSIDE_GEOFENCE', 'Anda di luar radius lokasi kerja.');
        }

        $now = now();
        [$start, $end] = $this->calculation->shiftPeriod($absensi->tanggal, $absensi->jadwalKerja->shift->jam_mulai, $absensi->jadwalKerja->shift->jam_selesai);
        $metrics = $this->calculation->calculate($absensi->clock_in_at, $now, $start, $end, $absensi->jadwalKerja->shift->toleransi_menit);
        $absensi->update(array_merge($metrics, ['clock_out_at' => $now, 'jam_pulang' => $now->format('H:i:s'), 'lat_pulang' => $validated['latitude'], 'lng_pulang' => $validated['longitude'], 'status_kehadiran' => 'selesai', 'is_mocked_pulang' => false]));

        return response()->json(['message' => 'Absen pulang berhasil.', 'server_time' => $now->toIso8601String(), 'data' => $this->recordData($absensi->fresh('jadwalKerja.shift', 'jadwalKerja.lokasiKerja'))]);
    }

    public function history(Request $request)
    {
        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        [$year, $month] = explode('-', $validated['month'] ?? now()->format('Y-m'));
        $records = Absensi::with('jadwalKerja.shift', 'jadwalKerja.lokasiKerja')->where('karyawan_id', $request->user()->karyawan->id)
            ->whereYear('tanggal', $year)->whereMonth('tanggal', $month)->orderByDesc('tanggal')->get();

        return response()->json(['data' => $records->map(fn (Absensi $record) => $this->recordData($record))->values(), 'period' => sprintf('%04d-%02d', $year, $month)]);
    }

    private function error(string $code, string $message, int $status = 422)
    {
        return response()->json(['code' => $code, 'message' => $message], $status);
    }

    private function recordData(Absensi $record): array
    {
        $record->loadMissing('jadwalKerja.shift', 'jadwalKerja.lokasiKerja');
        $data = $record->toArray();
        $data['tanggal_shift'] = $record->tanggal?->format('Y-m-d');
        $data['clock_in_at'] = $record->clock_in_at?->setTimezone(config('app.timezone'))->toIso8601String();
        $data['clock_out_at'] = $record->clock_out_at?->setTimezone(config('app.timezone'))->toIso8601String();
        $data['attendance_status'] = $record->late_minutes > 0 ? 'terlambat' : 'tepat_waktu';
        $data['session_status'] = match ($record->status_kehadiran) {
            'berjalan' => 'sedang_bekerja',
            'selesai' => 'selesai',
            'tidak_lengkap' => 'tidak_lengkap',
            default => $record->status_kehadiran,
        };
        $data['estimated_salary'] = $this->calculation->salary($record->paid_minutes, config('payroll.hourly_rate', 10000));
        $data['schedule'] = $record->jadwalKerja ? $this->scheduleData($record->jadwalKerja) : null;
        $data['shift'] = $data['schedule']['shift'] ?? null;
        $data['location'] = $data['schedule']['location'] ?? null;

        unset($data['jadwal_kerja']);

        return $data;
    }

    private function scheduleData(JadwalKerja $schedule): array
    {
        $schedule->loadMissing('shift', 'lokasiKerja');
        [$start, $end] = $this->calculation->shiftPeriod($schedule->tanggal_operasional, $schedule->shift->jam_mulai, $schedule->shift->jam_selesai);

        return [
            'id' => $schedule->id,
            'tanggal_shift' => $schedule->tanggal_operasional->format('Y-m-d'),
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $end->toIso8601String(),
            'shift' => $schedule->shift,
            'location' => $schedule->lokasiKerja,
        ];
    }
}
