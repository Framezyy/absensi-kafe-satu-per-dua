<?php

namespace App\Services;

use App\Models\Izin;
use Carbon\CarbonInterface;

class ApprovedLeaveService
{
    public function covers(int $employeeId, CarbonInterface $operationalDate): bool
    {
        return Izin::where('karyawan_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('tanggal_mulai', '<=', $operationalDate)
            ->where(function ($query) use ($operationalDate) {
                $query->whereDate('tanggal_selesai', '>=', $operationalDate)
                    ->orWhere(function ($query) use ($operationalDate) {
                        $query->whereNull('tanggal_selesai')->whereDate('tanggal_mulai', $operationalDate);
                    });
            })
            ->exists();
    }
}
