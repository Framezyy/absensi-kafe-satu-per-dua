@extends("layouts.admin")
@section("title", "Monitor Absensi")
@section("content")

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-sm text-stone-500">Status operasional berdasarkan jadwal kerja hari ini.</p><p class="mt-1 text-xs text-stone-400">Belum absen = jadwal hari ini - sudah clock-in - izin disetujui.</p></div><a href="{{ route('admin.monitor.index') }}" class="flex items-center justify-center gap-2 rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 text-sm font-semibold text-stone-600 shadow-sm"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>Refresh</a></div>

@php
    $stats = [
        ['Dijadwalkan', $summary['scheduled'], '#eff6ff', '#2563eb'],
        ['Sudah Clock-in', $summary['checked_in'], '#ecfdf5', '#059669'],
        ['Terlambat', $summary['late'], '#fffbeb', '#d97706'],
        ['Izin', $summary['leave'], '#faf5ff', '#9333ea'],
        ['Belum Absen', $summary['not_clocked_in'], '#f5f5f4', '#78716c'],
        ['Tidak Lengkap', $summary['incomplete'], '#fef2f2', '#dc2626'],
    ];
@endphp
<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6">@foreach($stats as $stat)<div class="rounded-3xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur"><div class="text-2xl font-extrabold" style="color:{{ $stat[3] }}">{{ $stat[1] }}</div><div class="mt-1 text-xs text-stone-400">{{ $stat[0] }}</div><div class="mt-3 h-1.5 rounded-full" style="background:{{ $stat[2] }}"></div></div>@endforeach</div>

<div class="mb-5 flex items-start gap-3 rounded-3xl border border-amber-100 bg-amber-50/60 px-5 py-4"><svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><div class="text-xs leading-relaxed text-amber-900"><strong>Rumus keterlambatan:</strong> menit terlambat = max(0, jam masuk - (jam mulai shift + toleransi)). Status sesi dan status terlambat dicatat terpisah.</div></div>

<div class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur"><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b border-stone-100 text-left text-[11px] font-bold uppercase tracking-wider text-stone-400"><th class="px-6 py-4">Karyawan</th><th class="px-6 py-4">Shift/Jadwal</th><th class="px-6 py-4">Masuk</th><th class="px-6 py-4">Pulang</th><th class="px-6 py-4">Lokasi</th><th class="px-6 py-4">Status</th></tr></thead><tbody class="divide-y divide-stone-50">
@forelse($data as $row)<tr class="transition hover:bg-amber-50/40"><td class="px-6 py-4"><div class="flex items-center gap-3"><div class="flex h-9 w-9 items-center justify-center rounded-xl text-[11px] font-bold text-white" style="background:linear-gradient(135deg,#a8a29e,#78716c)">{{ strtoupper(substr($row->nama,0,2)) }}</div><div><div class="font-semibold text-stone-800">{{ $row->nama }}</div><div class="text-[11px] text-stone-400">{{ $row->jabatan }}</div></div></div></td><td class="px-6 py-4"><span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">{{ $row->shift }}</span><div class="mt-1 font-mono text-[11px] text-stone-400">{{ $row->jadwal }}</div></td><td class="px-6 py-4"><span class="font-mono {{ $row->terlambat ? 'font-bold text-amber-600' : 'text-stone-700' }}">{{ $row->jam_masuk }}</span>@if($row->terlambat)<div class="mt-1 text-[10px] font-bold text-amber-600">+{{ $row->late_minutes }} menit</div>@endif</td><td class="px-6 py-4 font-mono text-stone-700">{{ $row->jam_pulang }}</td><td class="px-6 py-4 text-xs text-stone-500">{{ $row->lokasi }}</td><td class="px-6 py-4">
@php $styles=['Selesai'=>'bg-green-50 text-green-700','Sedang Bekerja'=>'bg-blue-50 text-blue-700','Tidak Lengkap'=>'bg-red-50 text-red-600','Izin'=>'bg-purple-50 text-purple-700','Belum Absen'=>'bg-stone-100 text-stone-500']; @endphp
<span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $styles[$row->status] ?? 'bg-stone-100 text-stone-500' }}">{{ $row->status }}</span></td></tr>
@empty<tr><td colspan="6" class="px-6 py-16 text-center"><p class="text-sm font-semibold text-stone-500">Belum ada jadwal hari ini</p><p class="mt-1 text-xs text-stone-400">Karyawan tanpa jadwal tidak dihitung sebagai belum absen.</p></td></tr>@endforelse
</tbody></table></div></div>
@endsection
