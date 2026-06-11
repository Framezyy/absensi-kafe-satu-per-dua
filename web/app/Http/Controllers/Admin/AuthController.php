<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view("admin.login");
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            "username" => "required",
            "password" => "required",
        ]);

        // Mock: admin/password untuk Phase 2 (tanpa DB).
        if ($credentials["username"] === "admin" && $credentials["password"] === "password") {
            session(["admin_logged_in" => true, "admin_user" => ["nama" => "Administrator", "email" => "admin@kafe12.com"]]);
            return redirect()->route("admin.dashboard");
        }

        return back()->withErrors(["username" => "Username atau password salah."])->withInput();
    }

    public function logout()
    {
        session()->forget(["admin_logged_in", "admin_user"]);
        return redirect()->route("admin.login");
    }
}
