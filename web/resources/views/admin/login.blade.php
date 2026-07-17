@extends("layouts.auth")
@section("title", "Login Admin")
@section("content")
<div class="flex min-h-full">
    {{-- Kiri: brand panel (desktop) --}}
    <div class="relative hidden w-1/2 overflow-hidden lg:block" style="background: linear-gradient(160deg, #2b1a10 0%, #3d2417 50%, #5a3620 100%);">
        <div class="absolute -left-24 -top-24 h-96 w-96 rounded-full opacity-20 blur-3xl" style="background: #f59e0b;"></div>
        <div class="absolute -bottom-32 -right-16 h-96 w-96 rounded-full opacity-10 blur-3xl" style="background: #f59e0b;"></div>
        <div class="relative z-10 flex h-full flex-col justify-between p-14">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl shadow-lg" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h14a3 3 0 013 3a3 3 0 01-3 3h-1M4 8v6a4 4 0 004 4h5a4 4 0 004-4M4 8V6a2 2 0 012-2h9"/></svg>
                </div>
                <span class="text-lg font-extrabold text-white tracking-tight">Kafe Satu Per Dua</span>
            </div>
            <div>
                <h1 class="text-4xl font-extrabold leading-tight text-white">Sistem Absensi<br>& Penggajian Karyawan</h1>
                <p class="mt-4 max-w-md text-stone-300/80 leading-relaxed">Kelola kehadiran, verifikasi wajah, lokasi kerja, dan penggajian karyawan dalam satu panel yang rapi.</p>
            </div>
            <div></div>
        </div>
    </div>

    {{-- Kanan: form --}}
    <div class="flex w-full items-center justify-center px-6 lg:w-1/2" style="background: radial-gradient(1000px 500px at 50% 0%, #f6efe7, #f1ece5);">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center lg:hidden">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl shadow-lg" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <svg class="h-8 w-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h14a3 3 0 013 3a3 3 0 01-3 3h-1M4 8v6a4 4 0 004 4h5a4 4 0 004-4M4 8V6a2 2 0 012-2h9"/></svg>
                </div>
                <h1 class="text-xl font-extrabold text-stone-800">Kafe Satu Per Dua</h1>
            </div>

            <div class="rounded-3xl border border-white/60 bg-white/80 p-8 shadow-xl backdrop-blur-xl">
                <h2 class="text-2xl font-extrabold text-stone-800">Selamat Datang</h2>
                <p class="mt-1 text-sm text-stone-500">Masuk ke panel administrator</p>

                @if($errors->any())
                    <div class="mt-5 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route("admin.login.submit") }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-stone-700">Username</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <input type="text" name="username" value="{{ old("username") }}" required autofocus
                                   class="w-full rounded-2xl border border-stone-200 bg-white py-3.5 pl-12 pr-4 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15"
                                   placeholder="Masukkan username">
                        </div>
                    </div>
                    <div x-data="{ show: false }">
                        <label class="mb-1.5 block text-sm font-semibold text-stone-700">Password</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <input x-bind:type="show ? 'text' : 'password'" name="password" required
                                   class="w-full rounded-2xl border border-stone-200 bg-white py-3.5 pl-12 pr-12 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15"
                                   placeholder="Masukkan password">
                            <button type="button" @click="show = !show" class="absolute right-4 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600">
                                <svg x-show="!show" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="mt-2 flex w-full items-center justify-center gap-2 rounded-2xl py-3.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:shadow-amber-500/40" style="background: linear-gradient(135deg, #d97706, #b45309);">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection