<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ScheduleResolver;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(private ScheduleResolver $schedules) {}

    public function active(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        $session = $this->schedules->openAttendance($karyawan->id);
        $schedule = $session?->jadwalKerja ?: $this->schedules->forClockIn($karyawan->id, now());
        $lokasi = $schedule?->lokasiKerja;

        return response()->json(['data' => $lokasi]);
    }
}
