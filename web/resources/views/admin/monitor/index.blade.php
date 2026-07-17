@extends("layouts.admin")
@section("title", "Monitor Absensi")
@section("content")

<div class="mb-5 flex items-center justify-between">
    <p class="text-sm text-stone-500">Pantauan kehadiran karyawan secara real-time</p>
    <a href="{{ route("admin.monitor.index") }}" class="flex items-center gap-2 rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 text-sm font-semibold text-stone-600 shadow-sm backdrop-blur transition hover:bg-white">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        Refresh
    </a>
</div>

@php
    $stats = [
        ["Hadir", $hadir, "green", "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"],
        ["Terlambat", $terlambat, "amber", "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"],
        ["Izin", $izin, "purple", "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"],
        ["Belum Absen", $belum, "stone", "M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"],
    ];
    $pal = ["green"=>["#ecfdf5","#059669"],"amber"=>["#fffbeb","#d97706"],"purple"=>["#faf5ff","#9333ea"],"stone"=>["#f5f5f4","#78716c"]];
@endphp
<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach($stats as $s)
        @php $c = $pal[$s[2]]; @endphp
        <div class="flex items-center gap-4 rounded-3xl border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl" style="background: {{ $c[0] }};">
                <svg class="h-6 w-6" style="color: {{ $c[1] }};" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $s[3] }}"/></svg>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-stone-800">{{ $s[1] }}</div>
                <div class="text-xs text-stone-400">{{ $s[0] }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-stone-100 text-left text-[11px] font-bold uppercase tracking-wider text-stone-400">
                    <th class="px-6 py-4">Karyawan</th>
                    <th class="px-6 py-4">Masuk</th>
                    <th class="px-6 py-4">Pulang</th>
                    <th class="px-6 py-4">Lokasi</th>
                    <th class="px-6 py-4">Similarity</th>
                    <th class="px-6 py-4">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-50">
            @foreach($data as $a)
                <tr class="transition hover:bg-amber-50/40">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl text-[11px] font-bold text-white" style="background: linear-gradient(135deg, #a8a29e, #78716c);">{{ strtoupper(substr($a->nama, 0, 2)) }}</div>
                            <span class="font-semibold text-stone-800">{{ $a->nama }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-mono {{ $a->terlambat ? "font-bold text-amber-600" : "text-stone-700" }}">{{ $a->jam_masuk }}</span>
                        @if($a->terlambat)<span class="ml-1.5 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">TELAT</span>@endif
                    </td>
                    <td class="px-6 py-4 font-mono text-stone-700">{{ $a->jam_pulang }}</td>
                    <td class="px-6 py-4 text-xs text-stone-500">{{ $a->lokasi }}</td>
                    <td class="px-6 py-4">
                        @if($a->face_similarity)
                            <span class="rounded-lg bg-stone-100 px-2 py-1 font-mono text-xs font-semibold {{ $a->face_similarity >= 0.7 ? "text-green-600" : "text-red-500" }}">{{ number_format($a->face_similarity, 2) }}</span>
                        @else<span class="text-stone-300">—</span>@endif
                    </td>
                    <td class="px-6 py-4">
                        @if($a->status === "Hadir")<span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-[11px] font-semibold text-green-700"><span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>Hadir</span>
                        @elseif($a->status === "Izin")<span class="inline-flex items-center gap-1.5 rounded-full bg-purple-50 px-2.5 py-1 text-[11px] font-semibold text-purple-700"><span class="h-1.5 w-1.5 rounded-full bg-purple-500"></span>Izin</span>
                        @else<span class="inline-flex items-center gap-1.5 rounded-full bg-stone-100 px-2.5 py-1 text-[11px] font-semibold text-stone-500"><span class="h-1.5 w-1.5 rounded-full bg-stone-400"></span>Belum Absen</span>@endif
                    </td>
                </tr>
            @endforeach
            @if($data->isEmpty())
                <tr><td colspan="6" class="px-6 py-16 text-center text-sm text-stone-400">Belum ada karyawan aktif.</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
@endsection