@extends("layouts.admin")
@section("title", "Audit Log Wajah")
@section("content")
<div class="mb-4"><h2 class="text-lg font-semibold">Audit Log Verifikasi Wajah</h2><p class="text-sm text-gray-500">Catatan semua verifikasi wajah (berhasil & gagal)</p></div>
<div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase"><tr><th class="px-5 py-3">Waktu</th><th class="px-5 py-3">Nama</th><th class="px-5 py-3">Aksi</th><th class="px-5 py-3">Challenge</th><th class="px-5 py-3">Similarity</th><th class="px-5 py-3">Foto</th><th class="px-5 py-3">Status</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
        @foreach($logs as $l)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-4 text-xs text-gray-500 font-mono">{{ $l->waktu }}</td>
                <td class="px-5 py-4 font-medium">{{ $l->nama }}</td>
                <td class="px-5 py-4">{{ $l->aksi }}</td>
                <td class="px-5 py-4"><span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">{{ $l->challenge }}</span></td>
                <td class="px-5 py-4 font-mono text-xs @if($l->similarity>=0.7) text-green-600 @else text-red-600 @endif">{{ number_format($l->similarity,2) }}</td>
                <td class="px-5 py-4 text-xs text-gray-400 font-mono">{{ $l->foto_path }}</td>
                <td class="px-5 py-4">@if($l->status=="Match")<span class="inline-flex rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Match</span>@else<span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Mismatch</span>@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<p class="text-xs text-gray-400 mt-3">Threshold: cosine similarity ≥ 0.7 = Match (Nusantoko & Prapanca, 2025)</p>
@endsection