<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\LokasiKerja;
use App\Services\GeofenceService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller {
    public function today(Request $request) {
        $karyawan = $request->user()->karyawan;
        $record = Absensi::where('karyawan_id', $karyawan->id)->where('tanggal', today())->first();
        return response()->json(['data' => $record]);
    }

    public function clockIn(Request $request) {
        $request->validate(['latitude' => 'required|numeric', 'longitude' => 'required|numeric', 'face_similarity_score' => 'nullable|numeric']);
        $karyawan = $request->user()->karyawan;
        $lokasi = $karyawan->lokasiKerja;
        if (!$lokasi) return response()->json(['message' => 'Lokasi kerja belum ditetapkan.'], 422);
        if (!GeofenceService::isInsideRadius($request->latitude, $request->longitude, $lokasi->latitude, $lokasi->longitude, $lokasi->radius_meter)) {
            return response()->json(['message' => 'Anda di luar radius lokasi kerja.'], 422);
        }
        $existing = Absensi::where('karyawan_id', $karyawan->id)->where('tanggal', today())->first();
        if ($existing && $existing->jam_masuk) return response()->json(['message' => 'Sudah absen masuk hari ini.'], 422);

        $jamMasuk = Carbon::now();
        $jamStandar = Carbon::parse(today()->format('Y-m-d') . ' ' . $lokasi->jam_masuk_standar)->addMinutes($lokasi->toleransi_menit);
        $status = $jamMasuk->gt($jamStandar) ? 'terlambat' : 'hadir';

        $absensi = Absensi::updateOrCreate(
            ['karyawan_id' => $karyawan->id, 'tanggal' => today()],
            ['jam_masuk' => $jamMasuk->format('H:i:s'), 'lat_masuk' => $request->latitude, 'lng_masuk' => $request->longitude, 'status_kehadiran' => $status, 'face_verified' => true, 'face_similarity_score' => $request->face_similarity_score]
        );
        return response()->json(['message' => 'Absen masuk berhasil.', 'data' => $absensi]);
    }

    public function clockOut(Request $request) {
        $request->validate(['latitude' => 'required|numeric', 'longitude' => 'required|numeric']);
        $karyawan = $request->user()->karyawan;
        $lokasi = $karyawan->lokasiKerja;
        if (!GeofenceService::isInsideRadius($request->latitude, $request->longitude, $lokasi->latitude, $lokasi->longitude, $lokasi->radius_meter)) {
            return response()->json(['message' => 'Anda di luar radius lokasi kerja.'], 422);
        }
        $absensi = Absensi::where('karyawan_id', $karyawan->id)->where('tanggal', today())->first();
        if (!$absensi || !$absensi->jam_masuk) return response()->json(['message' => 'Belum absen masuk hari ini.'], 422);
        if ($absensi->jam_pulang) return response()->json(['message' => 'Sudah absen pulang hari ini.'], 422);

        $absensi->update(['jam_pulang' => Carbon::now()->format('H:i:s'), 'lat_pulang' => $request->latitude, 'lng_pulang' => $request->longitude]);
        return response()->json(['message' => 'Absen pulang berhasil.', 'data' => $absensi]);
    }

    public function history(Request $request) {
        $karyawan = $request->user()->karyawan;
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);
        $records = Absensi::where('karyawan_id', $karyawan->id)->whereYear('tanggal', $year)->whereMonth('tanggal', $mon)->orderBy('tanggal', 'desc')->get();
        return response()->json(['data' => $records]);
    }
}