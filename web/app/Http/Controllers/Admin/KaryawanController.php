<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\LokasiKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class KaryawanController extends Controller {
    public function index() {
        $karyawan = Karyawan::with(['user', 'lokasiKerja', 'faceEmbedding'])->orderBy('nama_lengkap')->get();
        return view('admin.karyawan.index', compact('karyawan'));
    }

    public function create() {
        $lokasi = LokasiKerja::where('is_aktif', true)->get();
        return view('admin.karyawan.create', compact('lokasi'));
    }

    public function store(Request $request) {
        $request->validate([
            'nama' => 'required|string|max:255',
            'nik' => 'required|string|max:20|unique:karyawan,nik',
            'jabatan' => 'required|string|max:100',
            'tarif_harian' => 'required|integer|min:0',
            'tanggal_bergabung' => 'required|date',
            'lokasi_kerja_id' => 'nullable|exists:lokasi_kerja,id',
        ]);

        $user = User::create([
            'name' => $request->nama,
            'username' => strtolower(str_replace(' ', '', $request->nama)) . rand(10, 99),
            'email' => strtolower(str_replace(' ', '.', $request->nama)) . rand(10, 99) . '@kafe12.com',
            'password' => Hash::make('123456'),
            'role' => 'karyawan',
            'status' => 'aktif',
        ]);

        Karyawan::create([
            'user_id' => $user->id,
            'nik' => $request->nik,
            'nama_lengkap' => $request->nama,
            'jabatan' => $request->jabatan,
            'lokasi_kerja_id' => $request->lokasi_kerja_id,
            'tarif_gaji_harian' => $request->tarif_harian,
            'tgl_bergabung' => $request->tanggal_bergabung,
            'status' => 'aktif',
        ]);

        return redirect()->route('admin.karyawan.index')->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function edit($id) {
        $k = Karyawan::with('user')->findOrFail($id);
        $lokasi = LokasiKerja::where('is_aktif', true)->get();
        return view('admin.karyawan.edit', compact('k', 'lokasi'));
    }

    public function update(Request $request, $id) {
        $k = Karyawan::findOrFail($id);
        $request->validate([
            'nama' => 'required|string|max:255',
            'jabatan' => 'required|string|max:100',
            'tarif_harian' => 'required|integer|min:0',
            'status' => 'required|in:aktif,nonaktif',
            'lokasi_kerja_id' => 'nullable|exists:lokasi_kerja,id',
        ]);

        $k->update([
            'nama_lengkap' => $request->nama,
            'jabatan' => $request->jabatan,
            'tarif_gaji_harian' => $request->tarif_harian,
            'status' => $request->status,
            'lokasi_kerja_id' => $request->lokasi_kerja_id,
        ]);

        $k->user->update(['name' => $request->nama, 'status' => $request->status]);

        return redirect()->route('admin.karyawan.index')->with('success', 'Karyawan berhasil diperbarui.');
    }
}