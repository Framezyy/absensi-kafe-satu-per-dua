<?php
namespace App\Services;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\Bonus;
use App\Models\Penggajian;

class PayrollService {
    public static function recap(int $bulan, int $tahun): void {
        $karyawan = Karyawan::where('status', 'aktif')->get();
        foreach ($karyawan as $k) {
            $totalHadir = Absensi::where('karyawan_id', $k->id)->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->where('status_kehadiran', '!=', 'izin')->count();
            $totalBonus = Bonus::where('karyawan_id', $k->id)->where('periode_bulan', $bulan)->where('periode_tahun', $tahun)->sum('nominal');
            $honorarium = $totalHadir * $k->tarif_gaji_harian;
            Penggajian::updateOrCreate(
                ['karyawan_id' => $k->id, 'periode_bulan' => $bulan, 'periode_tahun' => $tahun],
                ['total_hadir' => $totalHadir, 'tarif_harian' => $k->tarif_gaji_harian, 'total_honorarium' => $honorarium, 'total_bonus' => $totalBonus, 'total_gaji' => $honorarium + $totalBonus]
            );
        }
    }
}