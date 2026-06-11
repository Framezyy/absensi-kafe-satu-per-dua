<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\Absensi;
use Carbon\Carbon;

class MonitorController extends Controller {
    public function index() {
        $today = Carbon::today();
        $karyawan = Karyawan::where('status', 'aktif')->with('user')->get();
        $absensiHariIni = Absensi::where('tanggal', $today)->with('karyawan')->get()->keyBy('karyawan_id');

        $data = $karyawan->map(function ($k) use ($absensiHariIni) {
            $absensi = $absensiHariIni->get($k->id);
            return (object)[
                'nama' => $k->nama_lengkap,
                'jam_masuk' => $absensi?->jam_masuk ? Carbon::parse($absensi->jam_masuk)->format('H:i') : '-',
                'jam_pulang' => $absensi?->jam_pulang ? Carbon::parse($absensi->jam_pulang)->format('H:i') : '-',
                'terlambat' => $absensi?->status_kehadiran === 'terlambat',
                'lokasi' => $k->lokasiKerja?->nama_lokasi ?? '-',
                'face_similarity' => $absensi?->face_similarity_score,
                'status' => $absensi?->jam_masuk ? 'Hadir' : ($absensi?->status_kehadiran === 'izin' ? 'Izin' : 'Belum Absen'),
            ];
        });

        $hadir = $data->where('status', 'Hadir')->count();
        $terlambat = $data->where('terlambat', true)->count();
        $izin = $data->where('status', 'Izin')->count();
        $belum = $data->where('status', 'Belum Absen')->count();

        return view('admin.monitor.index', compact('data', 'hadir', 'terlambat', 'izin', 'belum'));
    }
}