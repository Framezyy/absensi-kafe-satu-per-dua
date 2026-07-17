@extends("layouts.admin")
@section("title", "Audit Wajah")
@section("content")

<div class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-stone-100 text-left text-[11px] font-bold uppercase tracking-wider text-stone-400">
                    <th class="px-6 py-4">Waktu</th><th class="px-6 py-4">Karyawan</th><th class="px-6 py-4">Aksi</th><th class="px-6 py-4">Challenge</th><th class="px-6 py-4">Similarity</th><th class="px-6 py-4">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-50">
            @forelse($logs as $l)
                <tr class="transition hover:bg-amber-50/40">
                    <td class="px-6 py-4 font-mono text-xs text-stone-500">{{ $l->waktu }}</td>
                    <td class="px-6 py-4 font-semibold text-stone-800">{{ $l->nama }}</td>
                    <td class="px-6 py-4 text-stone-600">{{ $l->aksi }}</td>
                    <td class="px-6 py-4"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">{{ $l->challenge }}</span></td>
                    <td class="px-6 py-4"><span class="rounded-lg bg-stone-100 px-2.5 py-1 font-mono text-xs font-semibold @if($l->similarity >= 0.7) text-green-600 @else text-red-500 @endif">{{ number_format($l->similarity, 2) }}</span></td>
                    <td class="px-6 py-4">
                        @if($l->status == "Match")<span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-[11px] font-semibold text-green-700"><span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>Match</span>
                        @else<span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-600"><span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>{{ $l->status }}</span>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-16 text-center text-sm text-stone-400">Belum ada log verifikasi wajah.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection