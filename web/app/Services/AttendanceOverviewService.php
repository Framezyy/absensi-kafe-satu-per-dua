<?php

namespace App\Services;

use App\Models\Izin;
use App\Models\JadwalKerja;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AttendanceOverviewService
{
    public function forDate(CarbonInterface $date): Collection
    {
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
            $status = $this->resolveStatus($attendance?->status_kehadiran, (bool) $attendance?->clock_in_at, $onLeave);

            return (object) [
                'schedule_id' => $schedule->id,
                'nama' => $schedule->karyawan->nama_lengkap,
                'jabatan' => $schedule->karyawan->jabatan,
                'shift' => $schedule->shift->nama,
                'jadwal' => substr($schedule->shift->jam_mulai, 0, 5).'-'.substr($schedule->shift->jam_selesai, 0, 5),
                'jam_masuk' => $attendance?->clock_in_at?->format('H:i') ?? '-',
                'jam_pulang' => $attendance?->clock_out_at?->format('H:i') ?? '-',
                'late_minutes' => (int) ($attendance?->late_minutes ?? 0),
                'terlambat' => ($attendance?->late_minutes ?? 0) > 0,
                'lokasi' => $schedule->lokasiKerja->nama_lokasi,
                'face_similarity' => $attendance?->face_similarity_score,
                'status' => $status,
            ];
        });
    }

    public function resolveStatus(?string $sessionStatus, bool $hasClockIn, bool $onLeave): string
    {
        return match (true) {
            $onLeave && ! $hasClockIn => 'Izin',
            ! $hasClockIn => 'Belum Absen',
            $sessionStatus === 'tidak_lengkap' => 'Tidak Lengkap',
            $sessionStatus === 'selesai' => 'Selesai',
            default => 'Sedang Bekerja',
        };
    }

    public function summary(Collection $rows): array
    {
        return [
            'scheduled' => $rows->count(),
            'checked_in' => $rows->whereIn('status', ['Sedang Bekerja', 'Selesai', 'Tidak Lengkap'])->count(),
            'working' => $rows->where('status', 'Sedang Bekerja')->count(),
            'completed' => $rows->where('status', 'Selesai')->count(),
            'incomplete' => $rows->where('status', 'Tidak Lengkap')->count(),
            'late' => $rows->where('terlambat', true)->count(),
            'leave' => $rows->where('status', 'Izin')->count(),
            'not_clocked_in' => $rows->where('status', 'Belum Absen')->count(),
        ];
    }
}
