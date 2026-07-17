@extends("layouts.admin")
@section("title", "Approval Izin")
@section("content")

<p class="mb-5 text-sm text-stone-500">Kelola pengajuan izin dari karyawan</p>

<div class="space-y-3">
@forelse($izin as $i)
    <div class="rounded-3xl border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur transition hover:shadow-md">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl text-sm font-bold text-white" style="background: linear-gradient(135deg, #f59e0b, #d97706);">{{ strtoupper(substr($i->karyawan->nama_lengkap ?? "?", 0, 2)) }}</div>
                <div>
                    <div class="font-bold text-stone-800">{{ $i->karyawan->nama_lengkap ?? "N/A" }}</div>
                    <div class="mt-0.5 flex items-center gap-1.5 text-sm text-stone-500">
                        <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $i->tanggal_mulai->translatedFormat("d M Y") }}@if($i->tanggal_selesai && !$i->tanggal_mulai->isSameDay($i->tanggal_selesai)) — {{ $i->tanggal_selesai->translatedFormat("d M Y") }}@endif
                    </div>
                    <div class="mt-2 rounded-xl bg-stone-50 px-3 py-2 text-sm text-stone-600">{{ $i->alasan }}</div>
                    <div class="mt-1.5 text-xs text-stone-400">Diajukan {{ $i->created_at->translatedFormat("d M Y, H:i") }}</div>
                </div>
            </div>
            <div class="flex flex-shrink-0 flex-col items-end gap-2">
                @if($i->status === "pending")
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Menunggu</span>
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route("admin.izin.approve", $i->id) }}">@csrf
                            <button class="flex items-center gap-1.5 rounded-xl bg-green-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-green-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Setujui
                            </button>
                        </form>
                        <form method="POST" action="{{ route("admin.izin.reject", $i->id) }}">@csrf
                            <button class="flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-bold text-red-600 shadow-sm ring-1 ring-red-200 transition hover:bg-red-50">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>Tolak
                            </button>
                        </form>
                    </div>
                @elseif($i->status === "approved")
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Disetujui</span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>Ditolak</span>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-stone-200 bg-white/50 py-16 text-center">
        <svg class="h-14 w-14 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
        <p class="mt-3 text-sm font-medium text-stone-400">Belum ada pengajuan izin</p>
    </div>
@endforelse
</div>
@endsection