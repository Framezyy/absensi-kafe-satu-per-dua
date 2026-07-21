@extends("layouts.admin")
@section("title", "Jadwal Kerja")
@section("content")

@php
    $periodDate = \Carbon\Carbon::createFromFormat('Y-m', $period)->startOfMonth();
    $employeeCount = $schedules->pluck('karyawan_id')->unique()->count();
    $shiftCount = $schedules->pluck('shift_id')->unique()->count();
@endphp

<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm text-stone-500">Penempatan shift karyawan untuk periode {{ $periodDate->translatedFormat('F Y') }}.</p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 shadow-sm backdrop-blur"><span class="text-lg font-extrabold text-stone-800">{{ $schedules->count() }}</span><span class="ml-1 text-xs text-stone-400">jadwal</span></div>
            <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 shadow-sm backdrop-blur"><span class="text-lg font-extrabold text-green-600">{{ $employeeCount }}</span><span class="ml-1 text-xs text-stone-400">karyawan</span></div>
            <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 shadow-sm backdrop-blur"><span class="text-lg font-extrabold text-amber-600">{{ $shiftCount }}</span><span class="ml-1 text-xs text-stone-400">shift digunakan</span></div>
        </div>
    </div>
    <a href="{{ route('admin.jadwal.create') }}" class="flex items-center justify-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:shadow-amber-500/40" style="background: linear-gradient(135deg, #d97706, #b45309);">
        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>Tambah Jadwal
    </a>
</div>

<div class="mb-5 rounded-3xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur">
    <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="flex-1 sm:max-w-xs">
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-400">Periode Jadwal</label>
            <input type="month" name="month" value="{{ $period }}" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
        </div>
        <button class="flex items-center justify-center gap-2 rounded-2xl bg-stone-700 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-stone-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>Tampilkan
        </button>
    </form>
</div>

@if($schedules->isNotEmpty())
<div x-data="{ confirmDelete: false, delAction: '', delEmployee: '', delDate: '', openDelete(action, employee, date) { this.delAction = action; this.delEmployee = employee; this.delDate = date; this.confirmDelete = true; } }">
    <div class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-stone-100 text-left text-[11px] font-bold uppercase tracking-wider text-stone-400"><th class="px-6 py-4">Tanggal</th><th class="px-6 py-4">Karyawan</th><th class="px-6 py-4">Shift</th><th class="px-6 py-4">Lokasi</th><th class="px-6 py-4 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-stone-50">
                    @foreach($schedules as $schedule)
                        @php $isToday = $schedule->tanggal_operasional->isToday(); @endphp
                        <tr class="transition hover:bg-amber-50/40">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-stone-700">{{ $schedule->tanggal_operasional->translatedFormat('d M Y') }}</div>
                                <div class="mt-0.5 text-[11px] text-stone-400">{{ $schedule->tanggal_operasional->translatedFormat('l') }}</div>
                                @if($isToday)<span class="mt-1 inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700">HARI INI</span>@endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl text-xs font-bold text-white" style="background: linear-gradient(135deg, #f59e0b, #d97706);">{{ strtoupper(substr($schedule->karyawan->nama_lengkap, 0, 2)) }}</div><div><div class="font-semibold text-stone-800">{{ $schedule->karyawan->nama_lengkap }}</div><div class="text-[11px] text-stone-400">{{ $schedule->karyawan->jabatan }}</div></div></div>
                            </td>
                            <td class="px-6 py-4"><span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $schedule->shift->nama }}</span><div class="mt-1 font-mono text-[11px] text-stone-400">{{ substr($schedule->shift->jam_mulai, 0, 5) }}–{{ substr($schedule->shift->jam_selesai, 0, 5) }}</div></td>
                            <td class="px-6 py-4"><div class="flex items-center gap-2 text-stone-600"><svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span class="text-xs">{{ $schedule->lokasiKerja->nama_lokasi }}</span></div></td>
                            <td class="px-6 py-4 text-right"><button type="button" @click="openDelete('{{ route('admin.jadwal.destroy', $schedule) }}', '{{ addslashes($schedule->karyawan->nama_lengkap) }}', '{{ $schedule->tanggal_operasional->translatedFormat('d M Y') }}')" class="inline-flex items-center gap-1.5 rounded-xl bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-600 transition hover:bg-red-100 hover:text-red-600"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Hapus</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="confirmDelete" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 p-4 backdrop-blur-sm" @click.self="confirmDelete = false" @keydown.escape.window="confirmDelete = false">
        <div x-show="confirmDelete" x-transition class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-start gap-4"><div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-red-50"><svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.71-3L13.71 4a2 2 0 00-3.42 0L3.36 16a2 2 0 001.71 3z"/></svg></div><div><h3 class="text-base font-extrabold text-stone-800">Hapus jadwal kerja?</h3><p class="mt-1 text-sm text-stone-500">Jadwal <span class="font-semibold text-stone-700" x-text="delEmployee"></span> pada <span class="font-semibold text-stone-700" x-text="delDate"></span> akan dihapus. Jadwal yang sudah memiliki absensi tidak dapat dihapus.</p></div></div>
            <div class="mt-6 flex justify-end gap-3"><button type="button" @click="confirmDelete = false" class="rounded-2xl border border-stone-200 px-5 py-2.5 text-sm font-semibold text-stone-600">Batal</button><form method="POST" :action="delAction">@csrf @method('DELETE')<button class="rounded-2xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white">Ya, Hapus</button></form></div>
        </div>
    </div>
</div>
@else
<div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-stone-200 bg-white/50 py-16 text-center">
    <svg class="h-14 w-14 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    <p class="mt-3 text-sm font-semibold text-stone-500">Belum ada jadwal pada {{ $periodDate->translatedFormat('F Y') }}</p>
    <p class="mt-1 text-xs text-stone-400">Tambahkan jadwal agar karyawan dapat melakukan absensi.</p>
    <a href="{{ route('admin.jadwal.create') }}" class="mt-4 rounded-2xl bg-amber-700 px-4 py-2.5 text-sm font-bold text-white">Tambah Jadwal</a>
</div>
@endif
@endsection
