<?php

namespace App\Services;

use App\Models\Izin;
use App\Models\JadwalKerja;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AttendanceOverviewService
{
    public function __construct(private AttendanceCalculationService $calculation, private DailyScheduleMaterializer $materializer) {}

    public function forDate(CarbonInterface $date): Collection
    {
        $this->materializer->materializeForDate($date);
        $schedules = JadwalKerja::with(['karyawan', 'shift', 'lokasiKerja', 'absensi'])
            ->whereDate('tanggal_operasional', $date)
            ->orderBy('tanggal_operasional')
            ->get();

        $approvedLeaveEmployeeIds = Izin::where('status', 'approved')
            ->whereDate('tanggal_mulai', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereDate('tanggal_selesai', '>=', $date)
                    ->orWhere(function ($query) use ($date) {
                        $query->whereNull('tanggal_selesai')->whereDate('tanggal_mulai', $date);
                    });
            })
            ->pluck('karyawan_id')
            ->all();

        return $schedules->map(function (JadwalKerja $schedule) use ($approvedLeaveEmployeeIds) {
            $attendance = $schedule->absensi;
            $onLeave = in_array($schedule->karyawan_id, $approvedLeaveEmployeeIds, true);
            [, $scheduledEnd] = $this->calculation->shiftPeriod($schedule->tanggal_operasional, $schedule->shift->jam_mulai, $schedule->shift->jam_selesai);
            $earlyLeaveMinutes = $attendance?->status_kehadiran === 'selesai'
                ? $this->calculation->earlyLeaveMinutes($attendance->clock_out_at, $scheduledEnd)
                : 0;
            $status = $this->resolveStatus($attendance?->status_kehadiran, (bool) $attendance?->clock_in_at, $onLeave, $earlyLeaveMinutes);
            $arrivalStatus = match (true) {
                ! $attendance?->clock_in_at && $onLeave => 'Izin',
                ! $attendance?->clock_in_at => 'Belum Masuk',
                ($attendance->late_minutes ?? 0) > 0 => 'Terlambat',
                default => 'Tepat Waktu',
            };
            $departureStatus = match (true) {
                ! $attendance?->clock_in_at && $onLeave => 'Izin',
                ! $attendance?->clock_in_at => 'Belum Masuk',
                $attendance->status_kehadiran === 'tidak_lengkap' => 'Tidak Tercatat',
                ! $attendance?->clock_out_at => 'Belum Pulang',
                $earlyLeaveMinutes > 0 => 'Pulang Lebih Awal',
                default => 'Sesuai Jadwal',
            };

            return (object) [
                'schedule_id' => $schedule->id,
                'nama' => $schedule->karyawan->nama_lengkap,
                'jabatan' => $schedule->karyawan->jabatan,
                'shift' => $schedule->shift->nama,
                'jadwal' => substr($schedule->shift->jam_mulai, 0, 5).'-'.substr($schedule->shift->jam_selesai, 0, 5),
                'jam_masuk' => $attendance?->clock_in_at?->format('H:i') ?? '-',
                'jam_pulang' => $attendance?->clock_out_at?->format('H:i') ?? '-',
                'status_masuk' => $arrivalStatus,
                'status_pulang' => $departureStatus,
                'late_minutes' => (int) ($attendance?->late_minutes ?? 0),
                'terlambat' => ($attendance?->late_minutes ?? 0) > 0,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'pulang_lebih_awal' => $earlyLeaveMinutes > 0,
                'lokasi' => $schedule->lokasiKerja->nama_lokasi,
                'face_similarity' => $attendance?->face_similarity_score,
                'status' => $status,
            ];
        });
    }

    public function resolveStatus(?string $sessionStatus, bool $hasClockIn, bool $onLeave, int $earlyLeaveMinutes = 0): string
    {
        return match (true) {
            $onLeave && ! $hasClockIn => 'Izin',
            ! $hasClockIn => 'Belum Absen',
            $sessionStatus === 'tidak_lengkap' => 'Tidak Lengkap',
            $sessionStatus === 'selesai' && $earlyLeaveMinutes > 0 => 'Pulang Lebih Awal',
            $sessionStatus === 'selesai' => 'Selesai',
            default => 'Sedang Bekerja',
        };
    }

    public function summary(Collection $rows): array
    {
        return [
            'scheduled' => $rows->count(),
            'checked_in' => $rows->whereIn('status', ['Sedang Bekerja', 'Selesai', 'Pulang Lebih Awal', 'Tidak Lengkap'])->count(),
            'working' => $rows->where('status', 'Sedang Bekerja')->count(),
            'completed' => $rows->whereIn('status', ['Selesai', 'Pulang Lebih Awal'])->count(),
            'early_leave' => $rows->where('pulang_lebih_awal', true)->count(),
            'incomplete' => $rows->where('status', 'Tidak Lengkap')->count(),
            'late' => $rows->where('terlambat', true)->count(),
            'leave' => $rows->where('status', 'Izin')->count(),
            'not_clocked_in' => $rows->where('status', 'Belum Absen')->count(),
        ];
    }
}
