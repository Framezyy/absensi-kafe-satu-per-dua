@extends("layouts.admin")
@section("title", "Approval Izin")
@section("content")
<div class="mb-4"><h2 class="text-lg font-semibold">Pengajuan Izin Karyawan</h2></div>
<div class="space-y-4">
@forelse($izin as $i)
    <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
        <div class="flex items-start justify-between">
            <div>
                <div class="font-semibold">{{ $i->karyawan->nama_lengkap ?? 'N/A' }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ $i->tanggal_mulai->format('d M Y') }}@if($i->tanggal_selesai && !$i->tanggal_mulai->isSameDay($i->tanggal_selesai)) — {{ $i->tanggal_selesai->format('d M Y') }}@endif</div>
                <div class="text-sm mt-2">{{ $i->alasan }}</div>
                <div class="text-xs text-gray-400 mt-1">Diajukan: {{ $i->created_at->format('d M Y H:i') }}</div>
            </div>
            <div class="flex flex-col items-end gap-2">
                @if($i->status === "pending")
                    <span class="inline-flex rounded-full bg-yellow-50 px-3 py-1 text-xs font-medium text-yellow-700">Menunggu</span>
                    <div class="flex gap-2 mt-2">
                        <form method="POST" action="{{ route("admin.izin.approve", $i->id) }}">@csrf<button class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Setujui</button></form>
                        <form method="POST" action="{{ route("admin.izin.reject", $i->id) }}">@csrf<button class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">Tolak</button></form>
                    </div>
                @elseif($i->status === "approved")
                    <span class="inline-flex rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700">Disetujui</span>
                @else
                    <span class="inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700">Ditolak</span>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="rounded-xl bg-white p-8 shadow-sm border border-gray-100 text-center text-gray-400">Belum ada pengajuan izin</div>
@endforelse
</div>
@endsection