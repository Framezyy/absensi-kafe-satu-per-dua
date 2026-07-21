<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\Penggajian;

class PayrollService
{
    public function __construct(private AttendanceCalculationService $calculation) {}

    public function generate(int $bulan, int $tahun): void
    {
        $karyawan = Karyawan::whereHas('absensi', fn ($query) => $query->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun))->get();
        Penggajian::where('periode_bulan', $bulan)->where('periode_tahun', $tahun)->whereNotIn('karyawan_id', $karyawan->pluck('id'))->delete();
        foreach ($karyawan as $k) {
            $attendance = Absensi::where('karyawan_id', $k->id)->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
            $completed = (clone $attendance)->where('status_kehadiran', 'selesai')->get(['paid_minutes']);
            $totalPaidMinutes = $completed->sum('paid_minutes');
            $totalHadir = $completed->count();
            $totalTidakLengkap = (clone $attendance)->where('status_kehadiran', 'tidak_lengkap')->count();
            $hourlyRate = config('payroll.hourly_rate', 10000);
            $totalGaji = $completed->sum(fn (Absensi $record) => $this->calculation->salary($record->paid_minutes, $hourlyRate));
            Penggajian::updateOrCreate(
                ['karyawan_id' => $k->id, 'periode_bulan' => $bulan, 'periode_tahun' => $tahun],
                ['total_hadir' => $totalHadir, 'tarif_per_jam' => $hourlyRate, 'total_paid_minutes' => $totalPaidMinutes, 'total_tidak_lengkap' => $totalTidakLengkap, 'total_gaji' => $totalGaji]
            );
        }
    }
}
