@extends("layouts.admin")
@section("title", "Dashboard")
@section("content")
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
    <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3"><span class="text-sm text-gray-500">Karyawan Aktif</span><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">👥</span></div>
        <div class="text-2xl font-bold">{{ $karyawanAktif }}</div>
    </div>
    <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3"><span class="text-sm text-gray-500">Hadir Hari Ini</span><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-50 text-green-600">✅</span></div>
        <div class="text-2xl font-bold text-green-600">{{ $hadirHariIni }}</div>
    </div>
    <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3"><span class="text-sm text-gray-500">Terlambat</span><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-yellow-50 text-yellow-600">⏰</span></div>
        <div class="text-2xl font-bold text-yellow-600">{{ $terlambatHariIni }}</div>
    </div>
    <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3"><span class="text-sm text-gray-500">Pending Izin</span><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600">📋</span></div>
        <div class="text-2xl font-bold text-purple-600">{{ $pendingIzin }}</div>
    </div>
</div>
<div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
    <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
        <h3 class="text-base font-semibold mb-4">Aktivitas Terbaru</h3>
        <div class="space-y-3">
            @forelse($aktivitas as $a)
            <div class="flex items-center gap-3 text-sm">
                <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $a->status_kehadiran === 'terlambat' ? 'bg-yellow-100 text-yellow-600' : 'bg-green-100 text-green-600' }} text-xs">{{ $a->status_kehadiran === 'terlambat' ? '⏰' : '✓' }}</span>
                <div><span class="font-medium">{{ $a->karyawan->nama_lengkap }}</span> absen masuk pukul {{ \Carbon\Carbon::parse($a->jam_masuk)->format('H:i') }}{{ $a->status_kehadiran === 'terlambat' ? ' (terlambat)' : '' }}</div>
            </div>
            @empty
            <div class="text-sm text-gray-400">Belum ada aktivitas hari ini</div>
            @endforelse
        </div>
    </div>
    <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
        <h3 class="text-base font-semibold mb-4">Pengeluaran Bulan Ini</h3>
        <div class="mb-4 text-3xl font-bold text-amber-700">Rp {{ number_format($totalGajiBulan, 0, ',', '.') }}</div>
        <div class="text-sm text-gray-500">Total payroll bulan {{ now()->format('F Y') }}</div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex justify-between text-sm"><span class="text-gray-500">Karyawan aktif</span><span class="font-medium">{{ $karyawanAktif }} orang</span></div>
            <div class="flex justify-between text-sm mt-1"><span class="text-gray-500">Belum absen</span><span class="font-medium">{{ $belumAbsen }} orang</span></div>
        </div>
    </div>
</div>
@endsection