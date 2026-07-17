@extends("layouts.admin")
@section("title", "Rekap Payroll")
@section("content")

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="relative overflow-hidden rounded-3xl p-6 text-white shadow-lg sm:col-span-1" style="background: linear-gradient(150deg, #3d2417, #5a3620);">
        <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full opacity-20 blur-2xl" style="background:#f59e0b;"></div>
        <div class="relative">
            <div class="text-xs font-semibold uppercase tracking-wide text-amber-300/90">Total Pengeluaran</div>
            <div class="mt-2 text-2xl font-extrabold">Rp {{ number_format($totalPengeluaran, 0, ",", ".") }}</div>
            <div class="mt-1 text-xs text-stone-300/70">{{ now()->translatedFormat("F Y") }}</div>
        </div>
    </div>
    <div class="rounded-3xl border border-white/70 bg-white/80 p-6 shadow-sm backdrop-blur sm:col-span-2">
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <h3 class="text-sm font-extrabold text-stone-800">Cara Perhitungan Gaji</h3>
                <p class="mt-1 text-sm text-stone-500">Total gaji dihitung otomatis dari data absensi + bonus periode berjalan:</p>
                <div class="mt-3 rounded-xl bg-stone-50 px-4 py-2.5 font-mono text-sm text-stone-700">Total Gaji = (Hari Hadir × Tarif Harian) + Total Bonus</div>
            </div>
        </div>
    </div>
</div>

<div class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-stone-100 text-left text-[11px] font-bold uppercase tracking-wider text-stone-400">
                    <th class="px-6 py-4">Karyawan</th><th class="px-6 py-4">Hadir</th><th class="px-6 py-4">Telat</th><th class="px-6 py-4">Tarif/Hari</th><th class="px-6 py-4">Bonus</th><th class="px-6 py-4 text-right">Total Gaji</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-50">
            @foreach($payroll as $p)
                <tr class="transition hover:bg-amber-50/40">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl text-[11px] font-bold text-white" style="background: linear-gradient(135deg, #f59e0b, #d97706);">{{ strtoupper(substr($p->nama, 0, 2)) }}</div>
                            <div><div class="font-semibold text-stone-800">{{ $p->nama }}</div><div class="text-[11px] text-stone-400">{{ $p->jabatan }}</div></div>
                        </div>
                    </td>
                    <td class="px-6 py-4"><span class="font-mono font-semibold text-stone-700">{{ $p->hari_hadir }}</span> <span class="text-xs text-stone-400">hari</span></td>
                    <td class="px-6 py-4">@if($p->terlambat > 0)<span class="rounded bg-amber-100 px-2 py-0.5 font-mono text-xs font-bold text-amber-700">{{ $p->terlambat }}x</span>@else<span class="text-stone-300">0</span>@endif</td>
                    <td class="px-6 py-4 text-stone-600">Rp {{ number_format($p->tarif_harian, 0, ",", ".") }}</td>
                    <td class="px-6 py-4 text-green-600">Rp {{ number_format($p->total_bonus, 0, ",", ".") }}</td>
                    <td class="px-6 py-4 text-right"><span class="font-extrabold text-amber-700">Rp {{ number_format($p->total_gaji, 0, ",", ".") }}</span></td>
                </tr>
            @endforeach
            @if($payroll->isEmpty())
                <tr><td colspan="6" class="px-6 py-16 text-center text-sm text-stone-400">Belum ada data payroll.</td></tr>
            @endif
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-stone-100 bg-stone-50/60">
                    <td colspan="5" class="px-6 py-4 text-right text-sm font-bold text-stone-600">Total Pengeluaran</td>
                    <td class="px-6 py-4 text-right text-base font-extrabold text-amber-700">Rp {{ number_format($totalPengeluaran, 0, ",", ".") }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection