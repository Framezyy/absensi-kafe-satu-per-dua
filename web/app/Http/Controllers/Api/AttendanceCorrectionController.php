<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\AttendanceCorrection;
use Illuminate\Http\Request;

class AttendanceCorrectionController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['data' => AttendanceCorrection::with('absensi')->where('karyawan_id', $request->user()->karyawan->id)->latest()->get()]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'absensi_id' => $request->input('absensi_id', $request->input('attendance_id')),
            'requested_clock_out_at' => $request->input('requested_clock_out_at', $request->input('clock_out_at')),
            'alasan' => $request->input('alasan', $request->input('reason')),
        ]);
        $validated = $request->validate(['absensi_id' => 'required|integer', 'requested_clock_out_at' => 'required|date|before_or_equal:now', 'alasan' => 'required|string|max:1000']);
        $karyawan = $request->user()->karyawan;
        $absensi = Absensi::whereKey($validated['absensi_id'])->where('karyawan_id', $karyawan->id)->where('status_kehadiran', 'tidak_lengkap')->first();
        if (! $absensi) {
            return response()->json(['code' => 'NOT_CORRECTABLE', 'message' => 'Absensi tidak dapat dikoreksi.'], 422);
        }
        if (AttendanceCorrection::where('absensi_id', $absensi->id)->where('status', 'pending')->exists()) {
            return response()->json(['code' => 'CORRECTION_PENDING', 'message' => 'Koreksi absensi ini sedang diproses.'], 422);
        }
        if (now()->parse($validated['requested_clock_out_at'])->lessThanOrEqualTo($absensi->clock_in_at)) {
            return response()->json(['code' => 'INVALID_CLOCK_OUT', 'message' => 'Waktu pulang harus setelah waktu masuk.'], 422);
        }

        $correction = AttendanceCorrection::create($validated + ['karyawan_id' => $karyawan->id]);

        return response()->json(['message' => 'Permintaan koreksi dikirim.', 'data' => $correction], 201);
    }
}
