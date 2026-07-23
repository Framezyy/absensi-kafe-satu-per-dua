<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\Shift;
use App\Services\DailyScheduleMaterializer;
use Illuminate\Http\Request;

class JadwalKerjaController extends Controller
{
    public function index()
    {
        $karyawan = Karyawan::with('defaultShift', 'lokasiKerja')->orderBy('nama_lengkap')->get();
        $shifts = Shift::where('is_aktif', true)->whereIn('nama', ['Pagi', 'Malam'])->orderBy('jam_mulai')->get();

        return view('admin.jadwal.index', compact('karyawan', 'shifts'));
    }

    public function edit(Karyawan $karyawan)
    {
        $karyawan->load('defaultShift', 'lokasiKerja');
        $shifts = Shift::where('is_aktif', true)->whereIn('nama', ['Pagi', 'Malam'])->orderBy('jam_mulai')->get();

        return view('admin.jadwal.edit', compact('karyawan', 'shifts'));
    }

    public function update(Request $request, Karyawan $karyawan, DailyScheduleMaterializer $materializer)
    {
        $allowedShiftIds = array_column(Shift::where('is_aktif', true)->whereIn('nama', ['Pagi', 'Malam'])->get(['id'])->toArray(), 'id');
        $validated = $request->validate(['default_shift_id' => 'required|in:'.implode(',', $allowedShiftIds)]);
        $karyawan->update(['default_shift_id' => $validated['default_shift_id']]);
        if ($karyawan->status === 'aktif') {
            $materializer->applyDefaultShift($karyawan->fresh(), today());
        }

        return redirect()->route('admin.jadwal.index')->with('success', 'Shift kerja karyawan berhasil diperbarui.');
    }
}
