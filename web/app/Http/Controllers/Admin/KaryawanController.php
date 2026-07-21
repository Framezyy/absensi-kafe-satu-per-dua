<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\LokasiKerja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class KaryawanController extends Controller
{
    public function index()
    {
        $karyawan = Karyawan::with(['user', 'lokasiKerja', 'faceEmbedding'])->orderBy('nama_lengkap')->get();

        return view('admin.karyawan.index', compact('karyawan'));
    }

    public function create()
    {
        $lokasi = LokasiKerja::where('is_aktif', true)->get();

        return view('admin.karyawan.create', compact('lokasi'));
    }

    public function store(Request $request)
    {
        // Normalisasi username ke lowercase sebelum validasi & simpan.
        $request->merge([
            'username' => strtolower(trim($request->username ?? '')),
        ]);

        $request->validate([
            'nama' => 'required|string|max:255',
            'jabatan' => 'required|string|max:100',
            'username' => 'required|string|min:4|max:50|regex:/^[a-z0-9._]+$/|unique:users,username',
            'password' => 'required|string|size:12',
            'tanggal_bergabung' => 'required|date',
            'lokasi_kerja_id' => 'nullable|exists:lokasi_kerja,id',
        ], [
            'username.regex' => 'Username hanya boleh huruf kecil, angka, titik, dan garis bawah.',
            'username.unique' => 'Username sudah dipakai karyawan lain.',
            'password.size' => 'Password harus tepat 12 karakter.',
        ]);

        $user = User::create([
            'name' => $request->nama,
            'username' => $request->username,
            'email' => $request->username.'@kafe12.com',
            'password' => Hash::make($request->password),
            'role' => 'karyawan',
            'status' => 'aktif',
        ]);

        Karyawan::create([
            'user_id' => $user->id,
            'nama_lengkap' => $request->nama,
            'jabatan' => $request->jabatan,
            'lokasi_kerja_id' => $request->lokasi_kerja_id,
            'tarif_per_jam' => config('payroll.hourly_rate', 10000),
            'tgl_bergabung' => $request->tanggal_bergabung,
            'status' => 'aktif',
        ]);

        return redirect()->route('admin.karyawan.index')
            ->with('success', 'Karyawan berhasil ditambahkan.')
            ->with('new_credential', [
                'nama' => $request->nama,
                'username' => $request->username,
                'password' => $request->password,
            ]);
    }

    public function edit($id)
    {
        $k = Karyawan::with('user')->findOrFail($id);
        $lokasi = LokasiKerja::where('is_aktif', true)->get();

        return view('admin.karyawan.edit', compact('k', 'lokasi'));
    }

    public function update(Request $request, $id)
    {
        $k = Karyawan::with('user')->findOrFail($id);

        // Normalisasi username ke lowercase sebelum validasi.
        $request->merge([
            'username' => strtolower(trim($request->username ?? '')),
        ]);

        $request->validate([
            'nama' => 'required|string|max:255',
            'jabatan' => 'required|string|max:100',
            'status' => 'required|in:aktif,nonaktif',
            'lokasi_kerja_id' => 'nullable|exists:lokasi_kerja,id',
            'username' => 'required|string|min:4|max:50|regex:/^[a-z0-9._]+$/|unique:users,username,'.$k->user_id,
            'password' => 'nullable|string|size:12',
        ], [
            'username.regex' => 'Username hanya boleh huruf kecil, angka, titik, dan garis bawah.',
            'username.unique' => 'Username sudah dipakai karyawan lain.',
            'password.size' => 'Password harus tepat 12 karakter.',
        ]);

        $k->update([
            'nama_lengkap' => $request->nama,
            'jabatan' => $request->jabatan,
            'tarif_per_jam' => config('payroll.hourly_rate', 10000),
            'status' => $request->status,
            'lokasi_kerja_id' => $request->lokasi_kerja_id,
        ]);

        // Update akun login (nama, username, status). Password hanya jika diisi.
        $userData = [
            'name' => $request->nama,
            'username' => $request->username,
            'status' => $request->status,
        ];
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }
        $k->user->update($userData);

        return redirect()->route('admin.karyawan.index')->with('success', 'Karyawan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $k = Karyawan::with('user')->findOrFail($id);
        $nama = $k->nama_lengkap;

        // Hapus semua data terkait karyawan.
        $k->absensi()->delete();
        $k->faceEmbedding()->delete();
        $k->izin()->delete();
        $k->penggajian()->delete();

        // Simpan referensi user untuk dihapus setelah karyawan.
        $user = $k->user;

        $k->delete();

        // Hapus akun login (user) beserta token-nya.
        if ($user) {
            $user->tokens()->delete();
            $user->delete();
        }

        return redirect()->route('admin.karyawan.index')
            ->with('success', "Karyawan \"{$nama}\" beserta seluruh datanya berhasil dihapus.");
    }
}
