@extends("layouts.admin")
@section("title", "Shift Kerja")
@section("content")

@php
    $aktif = $shifts->where("is_aktif", true)->count();
    $lintasHari = $shifts->filter(fn($shift) => $shift->jam_selesai <= $shift->jam_mulai)->count();
@endphp

<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm text-stone-500">Atur jam operasional dan toleransi keterlambatan setiap shift karyawan.</p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 shadow-sm backdrop-blur">
                <span class="text-lg font-extrabold text-stone-800">{{ $shifts->count() }}</span>
                <span class="ml-1 text-xs text-stone-400">total shift</span>
            </div>
            <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 shadow-sm backdrop-blur">
                <span class="text-lg font-extrabold text-green-600">{{ $aktif }}</span>
                <span class="ml-1 text-xs text-stone-400">aktif</span>
            </div>
            <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 shadow-sm backdrop-blur">
                <span class="text-lg font-extrabold text-amber-600">{{ $lintasHari }}</span>
                <span class="ml-1 text-xs text-stone-400">lintas hari</span>
            </div>
        </div>
    </div>
    <a href="{{ route('admin.shifts.create') }}" class="flex items-center justify-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:shadow-amber-500/40" style="background: linear-gradient(135deg, #d97706, #b45309);">
        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        Tambah Shift
    </a>
</div>

@if($shifts->isNotEmpty())
<div class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur">
    <div class="border-b border-stone-100 px-6 py-4" style="background: linear-gradient(120deg, #faf5ef, #f5ede3);">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-extrabold text-stone-800">Daftar Shift</h3>
                <p class="text-xs text-stone-400">Durasi dibayar maksimal 8 jam atau 480 menit per shift.</p>
            </div>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-stone-100 text-left text-[11px] font-bold uppercase tracking-wider text-stone-400">
                    <th class="px-6 py-4">Nama Shift</th>
                    <th class="px-6 py-4">Jam Kerja</th>
                    <th class="px-6 py-4">Durasi</th>
                    <th class="px-6 py-4">Toleransi</th>
                    <th class="px-6 py-4">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-50">
                @foreach($shifts as $shift)
                    @php $overnight = $shift->jam_selesai <= $shift->jam_mulai; @endphp
                    <tr class="transition hover:bg-amber-50/40">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl text-xs font-bold text-white" style="background: linear-gradient(135deg, #f59e0b, #d97706);">{{ strtoupper(substr($shift->nama, 0, 1)) }}</div>
                                <div>
                                    <div class="font-semibold text-stone-800">{{ $shift->nama }}</div>
                                    <div class="text-[11px] text-stone-400">{{ $overnight ? 'Berakhir hari berikutnya' : 'Selesai di hari yang sama' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2 font-mono font-semibold text-stone-700">
                                <span class="rounded-lg bg-stone-100 px-2.5 py-1">{{ substr($shift->jam_mulai, 0, 5) }}</span>
                                <svg class="h-4 w-4 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-4-4l4 4-4 4"/></svg>
                                <span class="rounded-lg bg-stone-100 px-2.5 py-1">{{ substr($shift->jam_selesai, 0, 5) }}</span>
                            </div>
                        </td>
                        @php
                            [$shiftStart, $shiftEnd] = app(\App\Services\AttendanceCalculationService::class)->shiftPeriod(today(), $shift->jam_mulai, $shift->jam_selesai);
                            $shiftMinutes = $shiftStart->diffInMinutes($shiftEnd);
                        @endphp
                        <td class="px-6 py-4"><span class="font-semibold text-stone-700">{{ intdiv($shiftMinutes, 60) }}j {{ $shiftMinutes % 60 }}m</span><div class="text-[11px] text-stone-400">Batas dibayar {{ config('payroll.max_paid_minutes_per_shift') }} menit</div></td>
                        <td class="px-6 py-4"><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $shift->toleransi_menit }} menit</span></td>
                        <td class="px-6 py-4">
                            @if($shift->is_aktif)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-[11px] font-semibold text-green-700"><span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>Aktif</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-stone-100 px-2.5 py-1 text-[11px] font-semibold text-stone-500"><span class="h-1.5 w-1.5 rounded-full bg-stone-400"></span>Nonaktif</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-stone-200 bg-white/50 py-16 text-center">
    <svg class="h-14 w-14 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <p class="mt-3 text-sm font-semibold text-stone-500">Belum ada shift kerja</p>
    <p class="mt-1 text-xs text-stone-400">Tambahkan shift sebelum menyusun jadwal karyawan.</p>
    <a href="{{ route('admin.shifts.create') }}" class="mt-4 rounded-2xl bg-amber-700 px-4 py-2.5 text-sm font-bold text-white">Tambah Shift</a>
</div>
@endif
@endsection
