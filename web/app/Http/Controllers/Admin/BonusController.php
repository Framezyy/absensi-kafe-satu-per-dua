<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Bonus;
use App\Models\Karyawan;
use Illuminate\Http\Request;

class BonusController extends Controller {
    public function index() {
        $bonus = Bonus::with('karyawan')->orderBy('created_at', 'desc')->get();
        return view('admin.bonus.index', compact('bonus'));
    }

    public function create() {
        $karyawan = Karyawan::where('status', 'aktif')->orderBy('nama_lengkap')->get();
        return view('admin.bonus.create', compact('karyawan'));
    }

    public function store(Request $request) {
        $request->validate([
            'karyawan_id' => 'required|exists:karyawan,id',
            'periode' => 'required|string',
            'jumlah' => 'required|integer|min:0',
            'keterangan' => 'required|string',
        ]);

        [$bulan, $tahun] = explode(' ', $request->periode . ' ' . date('Y'));
        $bulanNum = array_search($bulan, ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']) + 1;

        Bonus::create([
            'karyawan_id' => $request->karyawan_id,
            'periode_bulan' => $bulanNum ?: date('n'),
            'periode_tahun' => is_numeric($tahun) ? $tahun : date('Y'),
            'nominal' => $request->jumlah,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->route('admin.bonus.index')->with('success', 'Bonus berhasil ditambahkan.');
    }
}