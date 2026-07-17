@extends("layouts.admin")
@section("title", "Manajemen Bonus")
@section("content")

@php $totalBonus = $bonus->sum("nominal"); @endphp

<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-4 rounded-3xl border border-white/70 bg-white/80 px-5 py-3.5 shadow-sm backdrop-blur">
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl" style="background:#ecfdf5;">
            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8a2 2 0 012-2h10a2 2 0 012 2v2H5V8zM4 10h16v9a1 1 0 01-1 1H5a1 1 0 01-1-1v-9zM12 6V4m0 2c-1.5 0-3-1-3-2.5S10.5 2 12 4c1.5-2 3-1.5 3 0S13.5 6 12 6zM12 10v10"/></svg>
        </div>
        <div>
            <div class="text-xs text-stone-400">Total Bonus Tercatat</div>
            <div class="text-xl font-extrabold text-stone-800">Rp {{ number_format($totalBonus, 0, ",", ".") }}</div>
        </div>
    </div>
    <a href="{{ route("admin.bonus.create") }}" class="flex items-center gap-2 self-start rounded-2xl px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:shadow-amber-500/40 sm:self-auto" style="background: linear-gradient(135deg, #d97706, #b45309);">
        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>Tambah Bonus
    </a>
</div>

@php $namaBulan = [1=>"Jan",2=>"Feb",3=>"Mar",4=>"Apr",5=>"Mei",6=>"Jun",7=>"Jul",8=>"Agu",9=>"Sep",10=>"Okt",11=>"Nov",12=>"Des"]; @endphp
<div class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-stone-100 text-left text-[11px] font-bold uppercase tracking-wider text-stone-400">
                    <th class="px-6 py-4">Karyawan</th><th class="px-6 py-4">Periode</th><th class="px-6 py-4">Nominal</th><th class="px-6 py-4">Keterangan</th><th class="px-6 py-4">Dibuat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-50">
            @forelse($bonus as $b)
                <tr class="transition hover:bg-amber-50/40">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl text-[11px] font-bold text-white" style="background: linear-gradient(135deg, #f59e0b, #d97706);">{{ strtoupper(substr($b->karyawan->nama_lengkap ?? "?", 0, 2)) }}</div>
                            <span class="font-semibold text-stone-800">{{ $b->karyawan->nama_lengkap ?? "N/A" }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-stone-600">{{ $namaBulan[$b->periode_bulan] ?? $b->periode_bulan }} {{ $b->periode_tahun }}</td>
                    <td class="px-6 py-4"><span class="rounded-lg bg-green-50 px-2.5 py-1 font-semibold text-green-700">Rp {{ number_format($b->nominal, 0, ",", ".") }}</span></td>
                    <td class="px-6 py-4 max-w-xs text-stone-500">{{ $b->keterangan }}</td>
                    <td class="px-6 py-4 text-xs text-stone-400">{{ $b->created_at->translatedFormat("d M Y") }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-16 text-center text-sm text-stone-400">Belum ada bonus yang tercatat.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection