<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Penggajian;
use App\Services\AttendanceCalculationService;
use App\Services\PayrollService;
use DomainException;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->validate(['period' => 'nullable|date_format:Y-m'])['period'] ?? now()->format('Y-m');
        [$tahun, $bulan] = explode('-', $period);
        $payroll = Penggajian::with('karyawan')->where('periode_bulan', $bulan)->where('periode_tahun', $tahun)->get();
        $totalPengeluaran = $payroll->sum('total_gaji');

        return view('admin.payroll.index', compact('payroll', 'totalPengeluaran', 'period'));
    }

    public function generate(Request $request, PayrollService $service)
    {
        $period = $request->validate(['period' => 'required|date_format:Y-m'])['period'];
        [$tahun, $bulan] = explode('-', $period);
        try {
            $service->generate((int) $bulan, (int) $tahun);
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.payroll.index', ['period' => $period])->with('success', 'Snapshot payroll berhasil dibuat.');
    }

    public function show(Penggajian $payroll, AttendanceCalculationService $calculation)
    {
        $payroll->load('karyawan');
        $sessions = Absensi::with('jadwalKerja.shift')
            ->where('karyawan_id', $payroll->karyawan_id)
            ->whereMonth('tanggal', $payroll->periode_bulan)
            ->whereYear('tanggal', $payroll->periode_tahun)
            ->orderBy('tanggal')
            ->get()
            ->map(function (Absensi $attendance) use ($calculation, $payroll) {
                $attendance->session_salary = $attendance->status_kehadiran === 'selesai'
                    ? $calculation->salary($attendance->paid_minutes, $payroll->tarif_per_jam)
                    : 0;

                return $attendance;
            });

        return view('admin.payroll.show', compact('payroll', 'sessions'));
    }
}
