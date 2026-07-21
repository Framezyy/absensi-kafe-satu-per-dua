<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Izin;
use App\Models\Karyawan;
use App\Models\Penggajian;
use App\Services\AttendanceOverviewService;

class DashboardController extends Controller
{
    public function index(AttendanceOverviewService $overview)
    {
        $today = today();
        $rows = $overview->forDate($today);
        $summary = $overview->summary($rows);
        $karyawanAktif = Karyawan::where('status', 'aktif')->count();
        $pendingIzin = Izin::where('status', 'pending')->count();

        $aktivitas = Absensi::whereDate('tanggal', $today)
            ->with('karyawan')
            ->whereNotNull('clock_in_at')
            ->orderByDesc('clock_in_at')
            ->limit(5)
            ->get();

        $totalGajiBulan = Penggajian::where('periode_bulan', $today->month)
            ->where('periode_tahun', $today->year)
            ->sum('total_gaji');

        return view('admin.dashboard', compact('karyawanAktif', 'pendingIzin', 'aktivitas', 'totalGajiBulan', 'summary'));
    }
}
