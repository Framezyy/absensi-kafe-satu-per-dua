<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalKerja;
use App\Models\Karyawan;
use App\Models\LokasiKerja;
use App\Models\Shift;
use App\Services\ScheduleResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;

class JadwalKerjaController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->validate(['month' => 'nullable|date_format:Y-m'])['month'] ?? now()->format('Y-m');
        [$year, $month] = explode('-', $period);
        $schedules = JadwalKerja::with('karyawan', 'shift', 'lokasiKerja')->whereYear('tanggal_operasional', $year)->whereMonth('tanggal_operasional', $month)->orderBy('tanggal_operasional')->get();

        return view('admin.jadwal.index', compact('schedules', 'period'));
    }

    public function create()
    {
        return view('admin.jadwal.create', ['karyawan' => Karyawan::where('status', 'aktif')->orderBy('nama_lengkap')->get(), 'shifts' => Shift::where('is_aktif', true)->get(), 'locations' => LokasiKerja::where('is_aktif', true)->get()]);
    }

    public function store(Request $request, ScheduleResolver $schedules)
    {
        $validated = $request->validate(['karyawan_id' => 'required|exists:karyawan,id', 'shift_id' => 'required|exists:shifts,id', 'lokasi_kerja_id' => 'required|exists:lokasi_kerja,id', 'tanggal_operasional' => 'required|date']);
        if ($schedules->overlapsExisting((int) $validated['karyawan_id'], Carbon::parse($validated['tanggal_operasional']), (int) $validated['shift_id'])) {
            return back()->withInput()->with('error', 'Jadwal bertabrakan dengan shift karyawan yang sudah ada.');
        }
        JadwalKerja::create($validated);

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function destroy(JadwalKerja $jadwal)
    {
        if ($jadwal->absensi()->exists()) {
            return back()->with('error', 'Jadwal yang sudah memiliki absensi tidak dapat dihapus.');
        }
        $jadwal->delete();

        return back()->with('success', 'Jadwal berhasil dihapus.');
    }
}
