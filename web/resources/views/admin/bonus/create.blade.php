@extends("layouts.admin")
@section("title", "Tambah Bonus")
@section("content")
<div class="mx-auto max-w-2xl">
    <a href="{{ route("admin.bonus.index") }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 transition hover:text-amber-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>Kembali
    </a>
    <form method="POST" action="{{ route("admin.bonus.store") }}" class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur">
        @csrf
        <div class="border-b border-stone-100 px-7 py-5">
            <h3 class="text-base font-extrabold text-stone-800">Pemberian Bonus</h3>
            <p class="text-xs text-stone-400">Bonus akan ditambahkan ke rekap payroll periode terkait</p>
        </div>
        <div class="space-y-5 px-7 py-6">
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-stone-700">Karyawan</label>
                <select name="karyawan_id" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                    @foreach($karyawan as $k)<option value="{{ $k->id }}">{{ $k->nama_lengkap }} — {{ $k->jabatan }}</option>@endforeach
                </select>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-stone-700">Periode</label>
                    <input type="text" name="periode" value="{{ now()->translatedFormat("F Y") }}" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-stone-700">Jumlah</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-stone-400">Rp</span>
                        <input type="number" name="jumlah" value="100000" min="0" class="w-full rounded-2xl border border-stone-200 bg-white py-3 pl-10 pr-4 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                    </div>
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-stone-700">Keterangan</label>
                <textarea name="keterangan" rows="3" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15" placeholder="Contoh: Bonus kinerja bulan ini"></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-3 border-t border-stone-100 px-7 py-5">
            <a href="{{ route("admin.bonus.index") }}" class="rounded-2xl border border-stone-200 px-5 py-2.5 text-sm font-semibold text-stone-600 transition hover:bg-stone-50">Batal</a>
            <button type="submit" class="rounded-2xl px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:shadow-amber-500/40" style="background: linear-gradient(135deg, #d97706, #b45309);">Simpan Bonus</button>
        </div>
    </form>
</div>
@endsection