<?php

namespace App\Services;

use App\Models\JadwalKerja;
use App\Models\Karyawan;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

class DailyScheduleMaterializer
{
    public function materializeForDate(CarbonInterface $date, ?int $employeeId = null): Collection
    {
        return Karyawan::with('defaultShift')
            ->where('status', 'aktif')
            ->when($employeeId, fn ($query) => $query->whereKey($employeeId))
            ->whereNotNull('default_shift_id')
            ->whereNotNull('lokasi_kerja_id')
            ->get()
            ->filter(fn (Karyawan $employee) => $employee->defaultShift?->is_aktif)
            ->map(function (Karyawan $employee) use ($date) {
                $existing = JadwalKerja::where('karyawan_id', $employee->id)->whereDate('tanggal_operasional', $date)->first();
                if ($existing) {
                    return $existing;
                }

                try {
                    return JadwalKerja::create(['karyawan_id' => $employee->id, 'tanggal_operasional' => $date->toDateString(), 'shift_id' => $employee->default_shift_id, 'lokasi_kerja_id' => $employee->lokasi_kerja_id]);
                } catch (QueryException) {
                    return JadwalKerja::where('karyawan_id', $employee->id)->whereDate('tanggal_operasional', $date)->firstOrFail();
                }
            });
    }

    public function materializeWindow(CarbonInterface $date, ?int $employeeId = null): void
    {
        foreach ([$date->copy()->subDay(), $date, $date->copy()->addDay()] as $operationalDate) {
            $this->materializeForDate($operationalDate, $employeeId);
        }
    }

    public function applyDefaultShift(Karyawan $employee, CarbonInterface $effectiveDate): void
    {
        JadwalKerja::where('karyawan_id', $employee->id)
            ->whereDate('tanggal_operasional', '>=', $effectiveDate)
            ->whereDoesntHave('absensi')
            ->update(['shift_id' => $employee->default_shift_id, 'lokasi_kerja_id' => $employee->lokasi_kerja_id]);
        $this->materializeWindow($effectiveDate, $employee->id);
    }
}
