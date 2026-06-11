<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\LokasiKerja;

class LocationController extends Controller {
    public function active() {
        $lokasi = LokasiKerja::where('is_aktif', true)->first();
        return response()->json(['data' => $lokasi]);
    }
}