@extends("layouts.admin")
@section("title", "Rekap Payroll")
@section("content")
<div class="mb-4 flex items-center justify-between">
    <div>
        <h2 class="text-lg font-semibold">Rekap Payroll</h2>
        <p class="text-sm text-gray-500">Periode: Juni 2026</p>
    </div>
    <div class="rounded-xl bg-amber-50 border border-amber-200 px-5 py-3 text-right">
        <div class="text-xs text-amber-600">Total Pengeluaran</div>
        <div class="text-xl font-bold text-amber-700">Rp {{ number_format($totalPengeluaran,0,',','.') }}</div>
    </div>
</div>
<div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase"><tr><th class="px-5 py-3">Nama</th><th class="px-5 py-3">Jabatan</th><th class="px-5 py-3">Hadir</th><th class="px-5 py-3">Terlambat</th><th class="px-5 py-3">Tarif/Hari</th><th class="px-5 py-3">Bonus</th><th class="px-5 py-3">Total Gaji</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
        @foreach($payroll as $p)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-4 font-medium">{{ $p->nama }}</td>
                <td class="px-5 py-4 text-gray-500">{{ $p->jabatan }}</td>
                <td class="px-5 py-4 font-mono">{{ $p->hari_hadir }} hari</td>
                <td class="px-5 py-4">@if($p->terlambat>0)<span class="text-yellow-600 font-mono">{{ $p->terlambat }}x</span>@else<span class="text-gray-400">0</span>@endif</td>
                <td class="px-5 py-4">Rp {{ number_format($p->tarif_harian,0,',','.') }}</td>
                <td class="px-5 py-4 text-green-600">Rp {{ number_format($p->total_bonus,0,',','.') }}</td>
                <td class="px-5 py-4 font-bold text-amber-700">Rp {{ number_format($p->total_gaji,0,',','.') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot class="bg-gray-50 font-semibold">
            <tr><td colspan="6" class="px-5 py-3 text-right">Total</td><td class="px-5 py-3 text-amber-700">Rp {{ number_format($totalPengeluaran,0,',','.') }}</td></tr>
        </tfoot>
    </table>
</div>
<p class="text-xs text-gray-400 mt-3">Formula: Total Gaji = (Hadir × Tarif/Hari) + Bonus</p>
@endsection