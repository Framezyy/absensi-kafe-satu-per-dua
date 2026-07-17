<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield("title", "Dashboard") — Kafe Satu Per Dua</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(["resources/css/app.css", "resources/js/app.js"])
    <style>
        [x-cloak] { display: none !important; }
        :root { --font-sans: "Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif; }
        body { font-family: var(--font-sans); }
        .nav-link { position: relative; }
        .nav-link.active::before { content: ""; position: absolute; left: 0; top: 50%; transform: translateY(-50%); height: 60%; width: 3px; border-radius: 0 4px 4px 0; background: #f59e0b; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #d6c3b0; border-radius: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
    </style>
    @stack("head")
</head>
<body class="h-full antialiased text-stone-800" style="background: radial-gradient(1200px 600px at 100% 0%, #f6efe7 0%, #f3ede6 40%, #f1ece5 100%);">
<div class="flex h-full" x-data="{ sidebarOpen: false }">

    {{-- ═══════════ SIDEBAR ═══════════ --}}
    <aside class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col -translate-x-full transform transition-transform duration-300 lg:static lg:translate-x-0"
           :class="sidebarOpen ? "translate-x-0" : "-translate-x-full lg:translate-x-0""
           @click.outside="sidebarOpen = false">
        <div class="m-3 flex flex-1 flex-col overflow-hidden rounded-3xl text-stone-300 shadow-2xl"
             style="background: linear-gradient(165deg, #2b1a10 0%, #3d2417 45%, #4a2c1c 100%);">

            {{-- Brand --}}
            <div class="flex items-center gap-3 px-6 pt-7 pb-6">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl shadow-lg" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h14a3 3 0 013 3v0a3 3 0 01-3 3h-1M4 8v6a4 4 0 004 4h5a4 4 0 004-4M4 8V6a2 2 0 012-2h9M8 2v2M12 2v2"/></svg>
                </div>
                <div>
                    <div class="text-[15px] font-extrabold leading-tight text-white tracking-tight">Kafe Satu Per Dua</div>
                    <div class="text-[11px] font-medium text-amber-400/80 tracking-wide uppercase">Admin Panel</div>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-4 pb-4">
                <div class="px-3 pb-2 pt-3 text-[10px] font-bold uppercase tracking-widest text-stone-500">Utama</div>
                <x-nav-link route="admin.dashboard" :active="request()->routeIs('admin.dashboard')" label="Dashboard" icon="grid" />
                <x-nav-link route="admin.monitor.index" :active="request()->routeIs('admin.monitor.*')" label="Monitor Absensi" icon="activity" />

                <div class="px-3 pb-2 pt-4 text-[10px] font-bold uppercase tracking-widest text-stone-500">Manajemen</div>
                <x-nav-link route="admin.karyawan.index" :active="request()->routeIs('admin.karyawan.*')" label="Karyawan" icon="users" />
                <x-nav-link route="admin.lokasi.index" :active="request()->routeIs('admin.lokasi.*')" label="Lokasi Kerja" icon="map-pin" />
                <x-nav-link route="admin.izin.index" :active="request()->routeIs('admin.izin.*')" label="Approval Izin" icon="clipboard-check" />

                <div class="px-3 pb-2 pt-4 text-[10px] font-bold uppercase tracking-widest text-stone-500">Keuangan</div>
                <x-nav-link route="admin.bonus.index" :active="request()->routeIs('admin.bonus.*')" label="Bonus" icon="gift" />
                <x-nav-link route="admin.payroll.index" :active="request()->routeIs('admin.payroll.*')" label="Rekap Payroll" icon="wallet" />

                <div class="px-3 pb-2 pt-4 text-[10px] font-bold uppercase tracking-widest text-stone-500">Keamanan</div>
                <x-nav-link route="admin.audit.index" :active="request()->routeIs('admin.audit.*')" label="Audit Wajah" icon="shield" />
            </nav>

            {{-- Mini profile --}}
            <div class="m-4 rounded-2xl bg-white/5 p-3 backdrop-blur">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl text-sm font-bold text-white" style="background: linear-gradient(135deg, #f59e0b, #d97706);">A</div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-white">Administrator</div>
                        <div class="truncate text-[11px] text-stone-400">Panel Admin</div>
                    </div>
                    <form method="POST" action="{{ route("admin.logout") }}">
                        @csrf
                        <button type="submit" title="Keluar" class="flex h-9 w-9 items-center justify-center rounded-xl text-stone-400 transition hover:bg-red-500/20 hover:text-red-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    {{-- Overlay mobile --}}
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-stone-900/40 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>

    {{-- ═══════════ MAIN ═══════════ --}}
    <div class="flex flex-1 flex-col overflow-hidden">

        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-20 items-center justify-between px-4 lg:px-8">
            <div class="flex items-center gap-4">
                <button class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/70 text-stone-600 shadow-sm backdrop-blur lg:hidden" @click="sidebarOpen = !sidebarOpen">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div>
                    <div class="flex items-center gap-2 text-[12px] font-medium text-stone-400">
                        <span>Admin</span>
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        <span class="text-amber-700">@yield("title", "Dashboard")</span>
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-stone-800">@yield("title", "Dashboard")</h1>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden items-center gap-2 rounded-2xl bg-white/70 px-4 py-2.5 text-sm text-stone-500 shadow-sm backdrop-blur sm:flex">
                    <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="font-medium text-stone-700">{{ \Carbon\Carbon::now()->translatedFormat("l, d F Y") }}</span>
                </div>
            </div>
        </header>

        {{-- Content --}}
        <main class="flex-1 overflow-y-auto px-4 pb-8 lg:px-8">
            @if(session("success"))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="mb-5 flex items-center gap-3 rounded-2xl border border-green-200 bg-green-50 px-5 py-3.5 text-sm font-medium text-green-800 shadow-sm">
                    <svg class="h-5 w-5 flex-shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="flex-1">{{ session("success") }}</span>
                    <button @click="show = false" class="text-green-600 hover:text-green-800">&times;</button>
                </div>
            @endif
            @if(session("error"))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="mb-5 flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-3.5 text-sm font-medium text-red-800 shadow-sm">
                    <svg class="h-5 w-5 flex-shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="flex-1">{{ session("error") }}</span>
                    <button @click="show = false" class="text-red-600 hover:text-red-800">&times;</button>
                </div>
            @endif
            @yield("content")
        </main>
    </div>
</div>
@stack("scripts")
</body>
</html>