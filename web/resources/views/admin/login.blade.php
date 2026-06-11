@extends("layouts.auth")
@section("title", "Login Admin")
@section("content")
<div class="w-full max-w-md mx-auto">
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-white/10 text-4xl backdrop-blur">☕</div>
        <h1 class="text-2xl font-bold text-white">Kafe Satu Per Dua</h1>
        <p class="text-amber-200 text-sm mt-1">Dashboard Administrator</p>
    </div>
    <div class="rounded-2xl bg-white p-8 shadow-2xl">
        <h2 class="text-xl font-bold text-gray-800 mb-1 text-center">Masuk ke akun Anda</h2>
        <p class="text-sm text-gray-500 mb-6 text-center">Silakan masukkan kredensial admin</p>
        @if($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route("admin.login.submit") }}">
            @csrf
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Username</label>
                <input type="text" name="username" value="{{ old("username") }}" required autofocus class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition" placeholder="Masukkan username">
            </div>
            <div class="mb-6">
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition" placeholder="Masukkan password">
            </div>
            <button type="submit" class="w-full rounded-xl bg-amber-700 px-4 py-3 text-sm font-semibold text-white hover:bg-amber-800 focus:ring-2 focus:ring-amber-500/40 transition">Masuk</button>
        </form>
        <p class="mt-6 text-center text-xs text-gray-400">Akun uji: admin / password</p>
    </div>
</div>
@endsection