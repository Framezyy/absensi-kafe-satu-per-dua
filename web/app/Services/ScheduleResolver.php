<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\JadwalKerja;
use App\Models\Shift;
use Carbon\CarbonInterface;

class ScheduleResolver
{
    public function __construct(private AttendanceCalculationService $calculation, private DailyScheduleMaterializer $materializer) {}

    public function forClockIn(int $employeeId, CarbonInterface $now): ?JadwalKerja
    {
        $this->materializer->materializeWindow($now, $employeeId);
        $openingMinutes = (int) config('attendance.clock_in_open_before_minutes', 120);

        return JadwalKerja::with('shift', 'lokasiKerja', 'absensi')
            ->where('karyawan_id', $employeeId)
            ->whereBetween('tanggal_operasional', [$now->copy()->subDay()->toDateString(), $now->copy()->addDay()->toDateString()])
            ->orderBy('tanggal_operasional')
            ->orderBy('id')
            ->get()
            ->filter(function (JadwalKerja $schedule) use ($now, $openingMinutes) {
                if (! $schedule->shift || ! $schedule->lokasiKerja || $schedule->absensi?->clock_in_at) {
                    return false;
                }
                [$start, $end] = $this->period($schedule);

                return $now->betweenIncluded($start->subMinutes($openingMinutes), $end);
            })
            ->sortBy(function (JadwalKerja $schedule) use ($now) {
                [$start] = $this->period($schedule);
                $startedRank = $start->lessThanOrEqualTo($now) ? 0 : 1;

                return sprintf('%d-%012d-%s-%012d', $startedRank, abs($start->diffInSeconds($now, false)), $start->format('YmdHis'), $schedule->id);
            })
            ->first();
    }

    public function openAttendance(int $employeeId): ?Absensi
    {
        return Absensi::with('jadwalKerja.shift', 'jadwalKerja.lokasiKerja')
            ->where('karyawan_id', $employeeId)
            ->where('status_kehadiran', 'berjalan')
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->latest('clock_in_at')
            ->first();
    }

    public function period(JadwalKerja $schedule): array
    {
        return $this->calculation->shiftPeriod($schedule->tanggal_operasional, $schedule->shift->jam_mulai, $schedule->shift->jam_selesai);
    }

    public function overlapsExisting(int $employeeId, CarbonInterface $date, int $shiftId, ?int $exceptId = null): bool
    {
        $candidate = JadwalKerja::with('shift')->whereKey($exceptId)->first() ?? new JadwalKerja(['tanggal_operasional' => $date]);
        $candidate->tanggal_operasional = $date;
        $candidate->setRelation('shift', Shift::findOrFail($shiftId));
        [$newStart, $newEnd] = $this->period($candidate);

        return JadwalKerja::with('shift')->where('karyawan_id', $employeeId)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->whereBetween('tanggal_operasional', [$date->copy()->subDay()->toDateString(), $date->copy()->addDay()->toDateString()])
            ->get()
            ->contains(function (JadwalKerja $existing) use ($newStart, $newEnd) {
                [$existingStart, $existingEnd] = $this->period($existing);

                return $newStart->lessThan($existingEnd) && $existingStart->lessThan($newEnd);
            });
    }
}
