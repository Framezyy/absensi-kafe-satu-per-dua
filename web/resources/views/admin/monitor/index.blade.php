@extends("layouts.admin")
@section("title", "Monitor Absensi")
@section("content")
<div class="mb-4 flex items-center justify-between">
    <div>
        <h2 class="text-lg font-semibold">Monitor Absensi Hari Ini</h2>
        <p class="text-sm text-gray-500">{{ now()->format("l, d F Y") }}</p>
    </div>
    <a href="{{ route("admin.monitor.index") }}" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">🔄 Refresh</a>
</div>
<div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">
    <div class="rounded-xl bg-white p-4 shadow-sm border border-gray-100 text-center"><div class="text-2xl font-bold text-green-600">{{ $hadir }}</div><div class="text-xs text-gray-500">Hadir</div></div>
    <div class="rounded-xl bg-white p-4 shadow-sm border border-gray-100 text-center"><div class="text-2xl font-bold text-yellow-600">{{ $terlambat }}</div><div class="text-xs text-gray-500">Terlambat</div></div>
    <div class="rounded-xl bg-white p-4 shadow-sm border border-gray-100 text-center"><div class="text-2xl font-bold text-purple-600">{{ $izin }}</div><div class="text-xs text-gray-500">Izin</div></div>
    <div class="rounded-xl bg-white p-4 shadow-sm border border-gray-100 text-center"><div class="text-2xl font-bold text-gray-400">{{ $belum }}</div><div class="text-xs text-gray-500">Belum Absen</div></div>
</div>
<div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase"><tr><th class="px-5 py-3">Nama</th><th class="px-5 py-3">Jam Masuk</th><th class="px-5 py-3">Jam Pulang</th><th class="px-5 py-3">Terlambat</th><th class="px-5 py-3">Lokasi</th><th class="px-5 py-3">Similarity</th><th class="px-5 py-3">Status</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
        @foreach($data as $a)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-4 font-medium">{{ $a->nama }}</td>
                <td class="px-5 py-4 font-mono">{{ $a->jam_masuk }}</td>
                <td class="px-5 py-4 font-mono">{{ $a->jam_pulang }}</td>
                <td class="px-5 py-4">@if($a->terlambat)<span class="text-yellow-600 text-xs font-medium">Ya</span>@else<span class="text-gray-400">-</span>@endif</td>
                <td class="px-5 py-4 text-gray-500 text-xs">{{ $a->lokasi }}</td>
                <td class="px-5 py-4 font-mono text-xs">@if($a->face_similarity){{ number_format($a->face_similarity, 2) }}@else<span class="text-gray-400">-</span>@endif</td>
                <td class="px-5 py-4">@if($a->status === "Hadir")<span class="inline-flex rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Hadir</span>@elseif($a->status === "Izin")<span class="inline-flex rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700">Izin</span>@else<span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Belum</span>@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection