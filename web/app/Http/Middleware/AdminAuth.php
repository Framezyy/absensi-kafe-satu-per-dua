<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        $adminId = session('admin_user.id');
        $admin = $adminId ? User::find($adminId) : null;

        if (! session('admin_logged_in') || ! $admin || $admin->role !== 'admin' || $admin->status !== 'aktif') {
            $request->session()->forget(['admin_logged_in', 'admin_user']);

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
