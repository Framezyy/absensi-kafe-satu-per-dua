<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller {
    public function login(Request $request) {
        $request->validate(['username' => 'required', 'password' => 'required']);
        $user = User::where('username', $request->username)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['username' => ['Username atau password salah.']]);
        }
        $token = $user->createToken('mobile')->plainTextToken;
        $karyawan = $user->karyawan;
        $hasFaceEnrolled = $karyawan && $karyawan->faceEmbedding && $karyawan->faceEmbedding->is_aktif;
        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id, 'username' => $user->username, 'nama' => $user->name,
                'nik' => $karyawan->nik ?? '', 'jabatan' => $karyawan->jabatan ?? '',
                'tanggal_bergabung' => $karyawan->tgl_bergabung?->format('Y-m-d'),
                'status_aktif' => $user->status === 'aktif',
                'has_face_enrolled' => $hasFaceEnrolled,
            ],
        ]);
    }

    public function me(Request $request) {
        $user = $request->user();
        $karyawan = $user->karyawan;
        $hasFaceEnrolled = $karyawan && $karyawan->faceEmbedding && $karyawan->faceEmbedding->is_aktif;
        return response()->json([
            'id' => $user->id, 'username' => $user->username, 'nama' => $user->name,
            'nik' => $karyawan->nik ?? '', 'jabatan' => $karyawan->jabatan ?? '',
            'tanggal_bergabung' => $karyawan->tgl_bergabung?->format('Y-m-d'),
            'status_aktif' => $user->status === 'aktif',
            'has_face_enrolled' => $hasFaceEnrolled,
        ]);
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Berhasil logout.']);
    }
}