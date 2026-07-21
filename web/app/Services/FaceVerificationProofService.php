<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\JadwalKerja;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FaceVerificationProofService
{
    public function issue(User $user, string $action, float $similarity, ?JadwalKerja $schedule = null, ?Absensi $attendance = null): array
    {
        $token = Str::random(64);
        $expiresAt = now()->addSeconds((int) config('attendance.face_verification_ttl_seconds', 120));
        Cache::put($this->key($token), [
            'user_id' => $user->id,
            'employee_id' => $user->karyawan->id,
            'access_token_id' => $user->currentAccessToken()?->id,
            'action' => $action,
            'schedule_id' => $schedule?->id,
            'attendance_id' => $attendance?->id,
            'similarity' => $similarity,
        ], $expiresAt);

        return ['token' => $token, 'expires_at' => $expiresAt];
    }

    public function consume(string $token, User $user, string $action, ?int $scheduleId = null, ?int $attendanceId = null): ?array
    {
        $proof = Cache::pull($this->key($token));
        if (! is_array($proof)
            || (int) $proof['user_id'] !== $user->id
            || (int) $proof['employee_id'] !== $user->karyawan->id
            || $proof['access_token_id'] !== $user->currentAccessToken()?->id
            || $proof['action'] !== $action
            || $proof['schedule_id'] !== $scheduleId
            || $proof['attendance_id'] !== $attendanceId) {
            return null;
        }

        return $proof;
    }

    private function key(string $token): string
    {
        return 'face-verification:'.hash('sha256', $token);
    }
}
