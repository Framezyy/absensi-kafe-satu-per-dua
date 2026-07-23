@extends("layouts.admin")
@section("title", "Jadwal Kerja")
@section("content")
@php
    $aktif = $karyawan->where('status', 'aktif')->count();
    $pagi = $karyawan->filter(fn($k) => $k->defaultShift?->nama === 'Pagi')->count();
    $malam = $karyawan->filter(fn($k) => $k->defaultShift?->nama === 'Malam')->count();
@endphp

<div class="mb-5">
    <p class="text-sm text-stone-500">Penempatan shift tetap dipilih saat karyawan dibuat. Gunakan Edit hanya ketika karyawan berpindah dari Pagi ke Malam atau sebaliknya.</p>
    <div class="mt-3 flex flex-wrap gap-3">
        <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 shadow-sm"><span class="text-lg font-extrabold text-stone-800">{{ $karyawan->count() }}</span><span class="ml-1 text-xs text-stone-400">karyawan</span></div>
        <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 shadow-sm"><span class="text-lg font-extrabold text-green-600">{{ $aktif }}</span><span class="ml-1 text-xs text-stone-400">aktif</span></div>
        <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 shadow-sm"><span class="text-lg font-extrabold text-amber-600">{{ $pagi }}</span><span class="ml-1 text-xs text-stone-400">shift Pagi</span></div>
        <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 shadow-sm"><span class="text-lg font-extrabold text-indigo-600">{{ $malam }}</span><span class="ml-1 text-xs text-stone-400">shift Malam</span></div>
    </div>
</div>

<div class="mb-5 flex items-start gap-3 rounded-3xl border border-amber-100 bg-amber-50/60 px-5 py-4"><svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><p class="text-xs leading-relaxed text-amber-900">Jadwal harian dibuat otomatis dari shift karyawan. Mengubah shift tidak mengubah histori absensi atau jadwal yang sudah memiliki absensi.</p></div>

<div class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm"><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b border-stone-100 text-left text-[11px] font-bold uppercase tracking-wider text-stone-400"><th class="px-6 py-4">Karyawan</th><th class="px-6 py-4">Shift Tetap</th><th class="px-6 py-4">Jam Kerja</th><th class="px-6 py-4">Lokasi</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-stone-50">
@forelse($karyawan as $employee)<tr class="transition hover:bg-amber-50/40"><td class="px-6 py-4"><div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl text-xs font-bold text-white" style="background:linear-gradient(135deg,#f59e0b,#d97706)">{{ strtoupper(substr($employee->nama_lengkap,0,2)) }}</div><div><div class="font-semibold text-stone-800">{{ $employee->nama_lengkap }}</div><div class="text-[11px] text-stone-400">{{ $employee->jabatan }} · {{ $employee->kode_karyawan }}</div></div></div></td><td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $employee->defaultShift?->nama === 'Malam' ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700' }}">{{ $employee->defaultShift?->nama ?? 'Belum diatur' }}</span></td><td class="px-6 py-4 font-mono text-xs text-stone-600">@if($employee->defaultShift){{ substr($employee->defaultShift->jam_mulai,0,5) }}–{{ substr($employee->defaultShift->jam_selesai,0,5) }}@else-@endif</td><td class="px-6 py-4 text-xs text-stone-500">{{ $employee->lokasiKerja?->nama_lokasi ?? '-' }}</td><td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $employee->status === 'aktif' ? 'bg-green-50 text-green-700' : 'bg-stone-100 text-stone-500' }}">{{ ucfirst($employee->status) }}</span></td><td class="px-6 py-4 text-right"><a href="{{ route('admin.jadwal.edit',$employee) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-600 transition hover:bg-amber-100 hover:text-amber-700"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 11l6-6 4 4-6 6H9v-4zM5 19h14"/></svg>Edit Shift</a></td></tr>
@empty<tr><td colspan="6" class="px-6 py-16 text-center text-sm text-stone-400">Belum ada karyawan. Tambahkan karyawan terlebih dahulu.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
