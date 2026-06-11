<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminGuest
{
    public function handle(Request $request, Closure $next)
    {
        if (session("admin_logged_in")) {
            return redirect()->route("admin.dashboard");
        }
        return $next($request);
    }
}
