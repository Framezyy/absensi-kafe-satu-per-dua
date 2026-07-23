<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class AdminGuest
{
    public function handle(Request $request, Closure $next)
    {
        $admin = User::find(session('admin_user.id'));
        if (session('admin_logged_in') && $admin?->role === 'admin' && $admin->status === 'aktif') {
            return redirect()->route('admin.dashboard');
        }

        $request->session()->forget(['admin_logged_in', 'admin_user']);

        return $next($request);
    }
}
