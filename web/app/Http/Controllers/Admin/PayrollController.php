<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\Bonus;
use App\Models\Penggajian;
use Carbon\Carbon;

class PayrollController extends Controller {
    public function index() {
        $bulan = now()->month;
        $tahun = now()->year;
        $karyawan = Karyawan::where('status', 'aktif')->get();

        $payroll = $karyawan->map(function ($k) use ($bulan, $tahun) {
            $totalHadir = Absensi::where('karyawan_id', $k->id)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->whereNotNull('jam_masuk')
                ->count();
            $terlambat = Absensi::where('karyawan_id', $k->id)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->where('status_kehadiran', 'terlambat')
                ->count();
            $totalBonus = Bonus::where('karyawan_id', $k->id)
                ->where('periode_bulan', $bulan)
                ->where('periode_tahun', $tahun)
                ->sum('nominal');
            $honorarium = $totalHadir * $k->tarif_gaji_harian;

            return (object)[
                'nama' => $k->nama_lengkap,
                'jabatan' => $k->jabatan,
                'hari_hadir' => $totalHadir,
                'terlambat' => $terlambat,
                'tarif_harian' => $k->tarif_gaji_harian,
                'total_bonus' => $totalBonus,
                'total_gaji' => $honorarium + $totalBonus,
            ];
        });

        $totalPengeluaran = $payroll->sum('total_gaji');
        return view('admin.payroll.index', compact('payroll', 'totalPengeluaran'));
    }
}