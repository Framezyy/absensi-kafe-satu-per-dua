@extends("layouts.admin")
@section("title", "Tambah Shift")
@section("content")
<div class="mx-auto max-w-3xl">
    <a href="{{ route('admin.shifts.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 transition hover:text-amber-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke daftar shift
    </a>

    @if($errors->any())
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-3.5 text-sm text-red-700">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <ul class="list-disc space-y-0.5 pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.shifts.store') }}" class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur">
        @csrf
        <div class="flex items-center gap-4 border-b border-stone-100 px-7 py-5" style="background: linear-gradient(120deg, #faf5ef, #f5ede3);">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-white shadow-lg" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div><h3 class="text-base font-extrabold text-stone-800">Informasi Shift</h3><p class="text-xs text-stone-400">Tentukan nama, jam kerja, dan batas toleransi masuk.</p></div>
        </div>

        <div class="space-y-5 px-7 py-6">
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-stone-700">Nama Shift</label>
                <input name="nama" value="{{ old('nama') }}" required maxlength="50" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15" placeholder="Contoh: Pagi atau Malam">
                @error('nama')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-stone-700">Jam Mulai</label>
                    <input type="time" name="jam_mulai" value="{{ old('jam_mulai') }}" required class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                    @error('jam_mulai')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-stone-700">Jam Selesai</label>
                    <input type="time" name="jam_selesai" value="{{ old('jam_selesai') }}" required class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                    @error('jam_selesai')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-stone-700">Toleransi Keterlambatan</label>
                <div class="relative max-w-xs"><input type="number" name="toleransi_menit" value="{{ old('toleransi_menit', 15) }}" min="0" max="120" required class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 pr-20 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15"><span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-xs text-stone-400">menit</span></div>
                <p class="mt-1.5 text-xs text-stone-400">Karyawan dinyatakan terlambat setelah jam mulai ditambah toleransi.</p>
                @error('toleransi_menit')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-start gap-3 rounded-2xl border border-amber-100 bg-amber-50/60 px-4 py-3.5">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-xs leading-relaxed text-amber-800">Jam selesai yang lebih kecil atau sama dengan jam mulai dianggap berakhir pada hari berikutnya. Durasi dibayar tetap dibatasi maksimal 8 jam atau 480 menit.</p>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-stone-100 px-7 py-5 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.shifts.index') }}" class="rounded-2xl border border-stone-200 px-5 py-2.5 text-center text-sm font-semibold text-stone-600 transition hover:bg-stone-50">Batal</a>
            <button type="submit" class="rounded-2xl px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:shadow-amber-500/40" style="background: linear-gradient(135deg, #d97706, #b45309);">Simpan Shift</button>
        </div>
    </form>
</div>
@endsection
