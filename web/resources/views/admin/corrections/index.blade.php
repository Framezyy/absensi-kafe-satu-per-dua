@extends("layouts.admin")
@section("title", "Koreksi Absensi")
@section("content")

@php
    $pending = $corrections->where('status', 'pending')->count();
    $approved = $corrections->where('status', 'approved')->count();
    $rejected = $corrections->where('status', 'rejected')->count();
@endphp

<div class="mb-5">
    <p class="text-sm text-stone-500">Tinjau pengajuan jam pulang untuk absensi yang tidak lengkap.</p>
    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="flex items-center gap-3 rounded-3xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur"><div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50"><svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><div><div class="text-2xl font-extrabold text-stone-800">{{ $pending }}</div><div class="text-xs text-stone-400">Menunggu peninjauan</div></div></div>
        <div class="flex items-center gap-3 rounded-3xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur"><div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-green-50"><svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div><div><div class="text-2xl font-extrabold text-stone-800">{{ $approved }}</div><div class="text-xs text-stone-400">Telah disetujui</div></div></div>
        <div class="flex items-center gap-3 rounded-3xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur"><div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-red-50"><svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></div><div><div class="text-2xl font-extrabold text-stone-800">{{ $rejected }}</div><div class="text-xs text-stone-400">Telah ditolak</div></div></div>
    </div>
</div>

<div x-data="{ filter: 'semua', approveOpen: false, rejectOpen: false, action: '', employee: '', date: '', openApprove(url, name, attendanceDate) { this.action = url; this.employee = name; this.date = attendanceDate; this.approveOpen = true; }, openReject(url, name, attendanceDate) { this.action = url; this.employee = name; this.date = attendanceDate; this.rejectOpen = true; } }">
    @if($corrections->isNotEmpty())
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach([['semua','Semua',$corrections->count()],['pending','Menunggu',$pending],['approved','Disetujui',$approved],['rejected','Ditolak',$rejected]] as $tab)
                <button type="button" @click="filter = '{{ $tab[0] }}'" :class="filter === '{{ $tab[0] }}' ? 'bg-stone-800 text-white shadow-md' : 'border border-white/70 bg-white/80 text-stone-500 hover:bg-white'" class="rounded-2xl px-4 py-2 text-xs font-semibold transition">{{ $tab[1] }} <span class="ml-1 opacity-70">{{ $tab[2] }}</span></button>
            @endforeach
        </div>

        <div class="space-y-3">
            @foreach($corrections as $correction)
                @php
                    $statusLabel = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$correction->status] ?? ucfirst($correction->status);
                    $attendanceDate = $correction->absensi->tanggal->translatedFormat('d M Y');
                @endphp
                <div x-show="filter === 'semua' || filter === '{{ $correction->status }}'" class="rounded-3xl border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur transition hover:shadow-md">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex min-w-0 items-start gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl text-sm font-bold text-white" style="background: linear-gradient(135deg, #f59e0b, #d97706);">{{ strtoupper(substr($correction->karyawan->nama_lengkap ?? '?', 0, 2)) }}</div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2"><h3 class="font-bold text-stone-800">{{ $correction->karyawan->nama_lengkap ?? 'N/A' }}</h3>@if($correction->absensi->jadwalKerja?->shift)<span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold text-amber-700">SHIFT {{ strtoupper($correction->absensi->jadwalKerja->shift->nama) }}</span>@endif</div>
                                <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-stone-500">
                                    <span class="inline-flex items-center gap-1.5"><svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>{{ $attendanceDate }}</span>
                                    <span>Masuk <strong class="font-mono text-stone-700">{{ $correction->absensi->clock_in_at?->format('H:i') ?? '-' }}</strong></span>
                                    <span>Usulan pulang <strong class="font-mono text-stone-700">{{ $correction->requested_clock_out_at->format('d M, H:i') }}</strong></span>
                                </div>
                                <div class="mt-3 max-w-2xl rounded-2xl bg-stone-50 px-4 py-3 text-sm leading-relaxed text-stone-600"><span class="mr-1 text-xs font-bold uppercase tracking-wide text-stone-400">Alasan</span> {{ $correction->alasan }}</div>
                                @if($correction->catatan_admin)<p class="mt-2 text-xs text-stone-400">Catatan admin: <span class="text-stone-600">{{ $correction->catatan_admin }}</span></p>@endif
                            </div>
                        </div>
                        <div class="flex flex-shrink-0 flex-col items-start gap-3 lg:items-end">
                            @if($correction->status === 'pending')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>{{ $statusLabel }}</span>
                                <div class="flex gap-2"><button type="button" @click="openApprove('{{ route('admin.corrections.approve', $correction) }}', '{{ addslashes($correction->karyawan->nama_lengkap) }}', '{{ $attendanceDate }}')" class="inline-flex items-center gap-1.5 rounded-xl bg-green-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-green-700"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Setujui</button><button type="button" @click="openReject('{{ route('admin.corrections.reject', $correction) }}', '{{ addslashes($correction->karyawan->nama_lengkap) }}', '{{ $attendanceDate }}')" class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-bold text-red-600 shadow-sm ring-1 ring-red-200 transition hover:bg-red-50"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>Tolak</button></div>
                            @elseif($correction->status === 'approved')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>{{ $statusLabel }}</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>{{ $statusLabel }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-stone-200 bg-white/50 py-16 text-center"><svg class="h-14 w-14 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3"/></svg><p class="mt-3 text-sm font-semibold text-stone-500">Belum ada permintaan koreksi</p><p class="mt-1 text-xs text-stone-400">Pengajuan lupa absen pulang akan muncul di halaman ini.</p></div>
    @endif

    <div x-show="approveOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 p-4 backdrop-blur-sm" @click.self="approveOpen = false" @keydown.escape.window="approveOpen = false"><div x-show="approveOpen" x-transition class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl"><div class="flex items-start gap-4"><div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-green-50"><svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div><div><h3 class="text-base font-extrabold text-stone-800">Setujui koreksi?</h3><p class="mt-1 text-sm text-stone-500">Jam pulang <span class="font-semibold" x-text="employee"></span> pada <span class="font-semibold" x-text="date"></span> akan digunakan untuk menghitung ulang durasi dan gaji.</p></div></div><form method="POST" :action="action" class="mt-6">@csrf<div class="flex justify-end gap-3"><button type="button" @click="approveOpen = false" class="rounded-2xl border border-stone-200 px-5 py-2.5 text-sm font-semibold text-stone-600">Batal</button><button class="rounded-2xl bg-green-600 px-5 py-2.5 text-sm font-bold text-white">Ya, Setujui</button></div></form></div></div>
    <div x-show="rejectOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 p-4 backdrop-blur-sm" @click.self="rejectOpen = false" @keydown.escape.window="rejectOpen = false"><div x-show="rejectOpen" x-transition class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl"><div class="flex items-start gap-4"><div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-red-50"><svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></div><div><h3 class="text-base font-extrabold text-stone-800">Tolak koreksi?</h3><p class="mt-1 text-sm text-stone-500">Berikan alasan penolakan untuk pengajuan <span class="font-semibold" x-text="employee"></span>.</p></div></div><form method="POST" :action="action" class="mt-5">@csrf<label class="mb-1.5 block text-sm font-semibold text-stone-700">Catatan Admin</label><textarea name="catatan_admin" required maxlength="1000" rows="4" class="w-full resize-none rounded-2xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-red-400 focus:ring-4 focus:ring-red-500/10" placeholder="Jelaskan alasan pengajuan ditolak..."></textarea><div class="mt-5 flex justify-end gap-3"><button type="button" @click="rejectOpen = false" class="rounded-2xl border border-stone-200 px-5 py-2.5 text-sm font-semibold text-stone-600">Batal</button><button class="rounded-2xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white">Tolak Pengajuan</button></div></form></div></div>
</div>
@endsection
