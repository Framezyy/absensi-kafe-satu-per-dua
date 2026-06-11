<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Izin;
use Illuminate\Http\Request;

class LeaveController extends Controller {
    public function index(Request $request) {
        $karyawan = $request->user()->karyawan;
        $leaves = Izin::where('karyawan_id', $karyawan->id)->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $leaves]);
    }

    public function store(Request $request) {
        $request->validate(['tanggal_mulai' => 'required|date', 'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai', 'alasan' => 'required|string']);
        $karyawan = $request->user()->karyawan;
        $izin = Izin::create(['karyawan_id' => $karyawan->id, 'tanggal_mulai' => $request->tanggal_mulai, 'tanggal_selesai' => $request->tanggal_selesai ?? $request->tanggal_mulai, 'alasan' => $request->alasan]);
        return response()->json(['message' => 'Pengajuan izin berhasil.', 'data' => $izin], 201);
    }
}