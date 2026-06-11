<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\LokasiKerja;
use Illuminate\Http\Request;

class LokasiController extends Controller {
    public function index() {
        $lokasi = LokasiKerja::withCount('karyawan')->first();
        return view('admin.lokasi.index', compact('lokasi'));
    }

    public function update(Request $request, $id) {
        $lokasi = LokasiKerja::findOrFail($id);
        $request->validate([
            'nama_lokasi' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meter' => 'required|integer|between:10,500',
            'jam_masuk_standar' => 'required|date_format:H:i',
            'toleransi_menit' => 'required|integer|between:0,60',
        ]);

        $lokasi->update($request->only('nama_lokasi', 'latitude', 'longitude', 'radius_meter', 'jam_masuk_standar', 'toleransi_menit'));
        return redirect()->route('admin.lokasi.index')->with('success', 'Lokasi berhasil diperbarui.');
    }
}