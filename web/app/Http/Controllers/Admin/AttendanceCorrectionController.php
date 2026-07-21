<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\Penggajian;
use App\Services\AttendanceCalculationService;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceCorrectionController extends Controller
{
    public function __construct(private AttendanceCalculationService $calculation, private PayrollService $payroll) {}

    public function index()
    {
        return view('admin.corrections.index', ['corrections' => AttendanceCorrection::with('karyawan', 'absensi.jadwalKerja.shift')->latest()->get()]);
    }

    public function approve(Request $request, AttendanceCorrection $correction)
    {
        $absensi = $correction->absensi()->with('jadwalKerja.shift')->firstOrFail();
        $paidPayrollExists = Penggajian::where('karyawan_id', $absensi->karyawan_id)->where('periode_bulan', $absensi->tanggal->month)->where('periode_tahun', $absensi->tanggal->year)->where('status_bayar', 'sudah_dibayar')->exists();
        if ($paidPayrollExists) {
            return back()->with('error', 'Koreksi tidak dapat disetujui karena payroll periode ini sudah dibayar.');
        }
        $shift = $absensi->jadwalKerja?->shift;
        if (! $shift) {
            return back()->with('error', 'Jadwal absensi tidak tersedia.');
        }
        [$start, $end] = $this->calculation->shiftPeriod($absensi->tanggal, $shift->jam_mulai, $shift->jam_selesai);
        $metrics = $this->calculation->calculate($absensi->clock_in_at, $correction->requested_clock_out_at, $start, $end, $shift->toleransi_menit);

        $approved = DB::transaction(function () use ($request, $correction, $absensi, $metrics) {
            $locked = AttendanceCorrection::whereKey($correction->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                return false;
            }
            $absensi->update($metrics + ['clock_out_at' => $correction->requested_clock_out_at, 'jam_pulang' => $correction->requested_clock_out_at->format('H:i:s'), 'status_kehadiran' => 'selesai']);
            $locked->update(['status' => 'approved', 'catatan_admin' => $request->input('catatan_admin'), 'reviewed_by' => session('admin_user.id'), 'reviewed_at' => now()]);

            return true;
        });
        if (! $approved) {
            return back()->with('error', 'Koreksi sudah ditinjau.');
        }
        $this->payroll->generate($absensi->tanggal->month, $absensi->tanggal->year);

        return back()->with('success', 'Koreksi disetujui dan metrik dihitung ulang.');
    }

    public function reject(Request $request, AttendanceCorrection $correction)
    {
        if ($correction->status !== 'pending') {
            return back()->with('error', 'Koreksi sudah ditinjau.');
        }
        $validated = $request->validate(['catatan_admin' => 'required|string|max:1000']);
        $correction->update($validated + ['status' => 'rejected', 'reviewed_by' => session('admin_user.id'), 'reviewed_at' => now()]);

        return back()->with('success', 'Koreksi ditolak.');
    }
}
