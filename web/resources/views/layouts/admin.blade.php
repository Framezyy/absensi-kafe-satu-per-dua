<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield("title", "Dashboard") — Kafe Satu Per Dua</title>
    @vite(["resources/css/app.css", "resources/js/app.js"])
    @stack("head")
</head>
<body class="h-full bg-gray-50 text-gray-800 antialiased">
<div class="flex h-full" x-data="{ sidebarOpen: false }">
    <aside class="fixed inset-y-0 left-0 z-30 w-64 -translate-x-full transform bg-amber-900 text-amber-50 transition-transform duration-200 lg:static lg:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" @click.outside="sidebarOpen = false">
        <div class="flex h-16 items-center gap-3 border-b border-amber-800 px-5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-700/40 text-xl">☕</div>
            <div><div class="text-sm font-bold leading-tight">Kafe Satu Per Dua</div><div class="text-[11px] text-amber-300">Dashboard Admin</div></div>
        </div>
        <nav class="mt-4 space-y-1 px-3 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('admin.dashboard') ? 'bg-amber-800 text-white' : 'text-amber-200 hover:bg-amber-800/50' }}">🏠 Dashboard</a>
            <a href="{{ route('admin.karyawan.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('admin.karyawan.*') ? 'bg-amber-800 text-white' : 'text-amber-200 hover:bg-amber-800/50' }}">👥 Manajemen Karyawan</a>
            <a href="{{ route('admin.lokasi.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('admin.lokasi.*') ? 'bg-amber-800 text-white' : 'text-amber-200 hover:bg-amber-800/50' }}">📍 Lokasi Kerja</a>
            <a href="{{ route('admin.monitor.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('admin.monitor.*') ? 'bg-amber-800 text-white' : 'text-amber-200 hover:bg-amber-800/50' }}">📊 Monitor Absensi</a>
            <a href="{{ route('admin.izin.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('admin.izin.*') ? 'bg-amber-800 text-white' : 'text-amber-200 hover:bg-amber-800/50' }}">✅ Approval Izin</a>
            <a href="{{ route('admin.bonus.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('admin.bonus.*') ? 'bg-amber-800 text-white' : 'text-amber-200 hover:bg-amber-800/50' }}">🎁 Bonus</a>
            <a href="{{ route('admin.payroll.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('admin.payroll.*') ? 'bg-amber-800 text-white' : 'text-amber-200 hover:bg-amber-800/50' }}">💰 Rekap Payroll</a>
            <a href="{{ route('admin.audit.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('admin.audit.*') ? 'bg-amber-800 text-white' : 'text-amber-200 hover:bg-amber-800/50' }}">🛡️ Audit Log Wajah</a>
        </nav>
    </aside>
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-20 bg-black/40 lg:hidden" @click="sidebarOpen = false"></div>
    <div class="flex flex-1 flex-col overflow-hidden">
        <header class="flex h-16 items-center justify-between border-b bg-white px-4 shadow-sm lg:px-6">
            <button class="lg:hidden" @click="sidebarOpen = !sidebarOpen">
                <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="text-lg font-semibold">@yield("title", "Dashboard")</div>
            <div class="flex items-center gap-3" x-data="{ userMenu: false }">
                <div class="hidden text-right sm:block"><div class="text-sm font-medium">Administrator</div><div class="text-xs text-gray-400">admin@kafe12.com</div></div>
                <div class="relative">
                    <button @click="userMenu = !userMenu" class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-600 text-sm font-bold text-white hover:bg-amber-700 transition">A</button>
                    <div x-show="userMenu" x-transition @click.outside="userMenu = false" class="absolute right-0 top-12 w-48 rounded-xl bg-white py-2 shadow-lg border border-gray-100 z-50">
                        <div class="px-4 py-2 border-b border-gray-100"><div class="text-sm font-medium">Administrator</div><div class="text-xs text-gray-400">admin@kafe12.com</div></div>
                        <form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">🚪 Keluar</button></form>
                    </div>
                </div>
            </div>
        </header>
        <main class="flex-1 overflow-y-auto p-4 lg:p-6">
            @if(session("success"))<div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session("success") }}</div>@endif
            @if(session("error"))<div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session("error") }}</div>@endif
            @yield("content")
        </main>
    </div>
</div>
@stack("scripts")
</body>
</html>