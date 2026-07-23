<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ActiveEmployee
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $employee = $user?->karyawan;

        if (! $user || $user->role !== 'karyawan' || $user->status !== 'aktif' || ! $employee || $employee->status !== 'aktif') {
            return response()->json([
                'code' => 'ACCOUNT_INACTIVE',
                'message' => 'Akun karyawan tidak aktif atau tidak valid.',
            ], 403);
        }

        return $next($request);
    }
}
