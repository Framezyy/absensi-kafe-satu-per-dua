@extends("layouts.admin")
@section("title", "Manajemen Bonus")
@section("content")
<div class="mb-4 flex items-center justify-between">
    <h2 class="text-lg font-semibold">Bonus Karyawan</h2>
    <a href="{{ route("admin.bonus.create") }}" class="rounded-xl bg-amber-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-800 transition">+ Tambah Bonus</a>
</div>
<div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase"><tr><th class="px-5 py-3">Nama</th><th class="px-5 py-3">Periode</th><th class="px-5 py-3">Jumlah</th><th class="px-5 py-3">Keterangan</th><th class="px-5 py-3">Tanggal</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
        @forelse($bonus as $b)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-4 font-medium">{{ $b->karyawan->nama_lengkap ?? 'N/A' }}</td>
                <td class="px-5 py-4">{{ $b->periode_bulan }}/{{ $b->periode_tahun }}</td>
                <td class="px-5 py-4 font-semibold text-green-600">Rp {{ number_format($b->nominal, 0, ',', '.') }}</td>
                <td class="px-5 py-4 text-gray-500">{{ $b->keterangan }}</td>
                <td class="px-5 py-4 text-gray-400 text-xs">{{ $b->created_at->format('d M Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">Belum ada bonus</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection