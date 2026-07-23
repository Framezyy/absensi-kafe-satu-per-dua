<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate(['username' => 'required', 'password' => 'required']);
        // Normalisasi username ke lowercase supaya karyawan tidak gagal
        // login gara-gara huruf besar/kecil (AndiSaputra = andisaputra).
        $username = strtolower(trim($request->username));
        $user = User::whereRaw('LOWER(username) = ?', [$username])->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['username' => ['Username atau password salah.']]);
        }
        $karyawan = $user->karyawan;
        if ($user->role !== 'karyawan' || $user->status !== 'aktif' || ! $karyawan || $karyawan->status !== 'aktif') {
            throw ValidationException::withMessages(['username' => ['Akun karyawan tidak aktif atau tidak valid.']]);
        }
        $token = $user->createToken('mobile')->plainTextToken;
        $hasFaceEnrolled = $karyawan && $karyawan->faceEmbedding && $karyawan->faceEmbedding->is_aktif;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id, 'username' => $user->username, 'nama' => $user->name,
                'id_karyawan' => $karyawan->kode_karyawan ?? '', 'jabatan' => $karyawan->jabatan ?? '',
                'tanggal_bergabung' => $karyawan->tgl_bergabung?->format('Y-m-d'),
                'status_aktif' => $user->status === 'aktif',
                'has_face_enrolled' => $hasFaceEnrolled,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $karyawan = $user->karyawan;
        $hasFaceEnrolled = $karyawan && $karyawan->faceEmbedding && $karyawan->faceEmbedding->is_aktif;

        return response()->json([
            'id' => $user->id, 'username' => $user->username, 'nama' => $user->name,
            'id_karyawan' => $karyawan->kode_karyawan ?? '', 'jabatan' => $karyawan->jabatan ?? '',
            'tanggal_bergabung' => $karyawan->tgl_bergabung?->format('Y-m-d'),
            'status_aktif' => $user->status === 'aktif',
            'has_face_enrolled' => $hasFaceEnrolled,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }
}
