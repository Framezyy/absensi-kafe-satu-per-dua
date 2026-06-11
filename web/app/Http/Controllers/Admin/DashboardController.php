<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\Izin;
use App\Models\Penggajian;
use Carbon\Carbon;

class DashboardController extends Controller {
    public function index() {
        $today = Carbon::today();
        $karyawanAktif = Karyawan::where('status', 'aktif')->count();
        $hadirHariIni = Absensi::where('tanggal', $today)->whereNotNull('jam_masuk')->count();
        $terlambatHariIni = Absensi::where('tanggal', $today)->where('status_kehadiran', 'terlambat')->count();
        $pendingIzin = Izin::where('status', 'pending')->count();
        $belumAbsen = $karyawanAktif - $hadirHariIni;

        $aktivitas = Absensi::where('tanggal', $today)
            ->with('karyawan')
            ->whereNotNull('jam_masuk')
            ->orderBy('jam_masuk', 'desc')
            ->limit(5)
            ->get();

        $totalGajiBulan = Penggajian::where('periode_bulan', $today->month)
            ->where('periode_tahun', $today->year)
            ->sum('total_gaji');

        return view('admin.dashboard', compact(
            'karyawanAktif', 'hadirHariIni', 'terlambatHariIni',
            'pendingIzin', 'belumAbsen', 'aktivitas', 'totalGajiBulan'
        ));
    }
}