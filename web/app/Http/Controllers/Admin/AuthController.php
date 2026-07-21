<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Cek ke database: user dengan role admin (username case-insensitive).
        $user = User::whereRaw('LOWER(username) = ?', [strtolower($credentials['username'])])
            ->where('role', 'admin')
            ->first();

        if ($user && $user->status === 'aktif' && Hash::check($credentials['password'], $user->password)) {
            $request->session()->regenerate();
            session([
                'admin_logged_in' => true,
                'admin_user' => ['id' => $user->id, 'nama' => $user->name, 'email' => $user->email],
            ]);

            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors(['username' => 'Username atau password salah.'])->withInput();
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
