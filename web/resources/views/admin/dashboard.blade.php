@extends("layouts.admin")
@section("title", "Dashboard")
@section("content")

{{-- Stat cards --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @php
        $cards = [
            ["label" => "Karyawan Aktif", "value" => $karyawanAktif, "sub" => "total terdaftar", "color" => "blue", "icon" => "M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2.13a4 4 0 100-8 4 4 0 000 8z"],
            ["label" => "Jadwal Hari Ini", "value" => $summary['scheduled'], "sub" => "target kehadiran", "color" => "green", "icon" => "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"],
            ["label" => "Sudah Clock-in", "value" => $summary['checked_in'], "sub" => $summary['late'] . " terlambat", "color" => "amber", "icon" => "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"],
            ["label" => "Pending Izin", "value" => $pendingIzin, "sub" => "menunggu approval", "color" => "purple", "icon" => "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"],
        ];
        $palette = [
            "blue" => ["bg" => "#eff6ff", "fg" => "#2563eb", "ring" => "#dbeafe"],
            "green" => ["bg" => "#ecfdf5", "fg" => "#059669", "ring" => "#d1fae5"],
            "amber" => ["bg" => "#fffbeb", "fg" => "#d97706", "ring" => "#fef3c7"],
            "purple" => ["bg" => "#faf5ff", "fg" => "#9333ea", "ring" => "#f3e8ff"],
        ];
    @endphp
    @foreach($cards as $c)
        @php $p = $palette[$c["color"]]; @endphp
        <div class="group relative overflow-hidden rounded-3xl border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur transition hover:-translate-y-0.5 hover:shadow-lg">
            <div class="flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl" style="background: {{ $p["bg"] }};">
                    <svg class="h-6 w-6" style="color: {{ $p["fg"] }};" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $c["icon"] }}"/></svg>
                </div>
                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" style="background: {{ $p["bg"] }}; color: {{ $p["fg"] }};">Hari ini</span>
            </div>
            <div class="mt-4 text-3xl font-extrabold tracking-tight text-stone-800">{{ $c["value"] }}</div>
            <div class="mt-0.5 text-sm font-semibold text-stone-600">{{ $c["label"] }}</div>
            <div class="text-xs text-stone-400">{{ $c["sub"] }}</div>
        </div>
    @endforeach
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Aktivitas terbaru --}}
    <div class="lg:col-span-2 rounded-3xl border border-white/70 bg-white/80 p-6 shadow-sm backdrop-blur">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-stone-800">Aktivitas Absensi Terbaru</h3>
                <p class="text-xs text-stone-400">5 absensi masuk terakhir hari ini</p>
            </div>
            <a href="{{ route("admin.monitor.index") }}" class="rounded-xl bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-600 transition hover:bg-stone-200">Lihat Monitor</a>
        </div>
        <div class="space-y-2">
            @forelse($aktivitas as $a)
                <div class="flex items-center gap-4 rounded-2xl border border-stone-100 bg-white px-4 py-3 transition hover:border-amber-200 hover:bg-amber-50/40">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl text-sm font-bold text-white" style="background: linear-gradient(135deg, {{ $a->late_minutes > 0 ? "#f59e0b, #d97706" : "#10b981, #059669" }});">
                        {{ strtoupper(substr($a->karyawan->nama_lengkap ?? "?", 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-stone-800">{{ $a->karyawan->nama_lengkap ?? "-" }}</div>
                        <div class="text-xs text-stone-400">Absen masuk</div>
                    </div>
                    <div class="text-right">
                        <div class="font-mono text-sm font-bold text-stone-700">{{ $a->clock_in_at->format("H:i") }}</div>
                        @if($a->late_minutes > 0)
                            <span class="text-[11px] font-semibold text-amber-600">Terlambat</span>
                        @else
                            <span class="text-[11px] font-semibold text-green-600">Tepat waktu</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-stone-200 py-12 text-center">
                    <svg class="h-12 w-12 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p class="mt-3 text-sm font-medium text-stone-400">Belum ada aktivitas absensi hari ini</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Ringkasan pengeluaran + status --}}
    <div class="space-y-6">
        <div class="relative overflow-hidden rounded-3xl p-6 text-white shadow-lg" style="background: linear-gradient(150deg, #3d2417, #5a3620);">
            <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full opacity-20 blur-2xl" style="background: #f59e0b;"></div>
            <div class="relative">
                <div class="flex items-center gap-2 text-amber-300/90">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h.01M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                    <span class="text-xs font-semibold uppercase tracking-wide">Snapshot Payroll Bulan Ini</span>
                </div>
                <div class="mt-3 text-3xl font-extrabold">Rp {{ number_format($totalGajiBulan, 0, ",", ".") }}</div>
                <div class="mt-1 text-xs text-stone-300/70">{{ \Carbon\Carbon::now()->translatedFormat("F Y") }}</div>
                <a href="{{ route("admin.payroll.index") }}" class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-white/15 px-4 py-2 text-xs font-semibold backdrop-blur transition hover:bg-white/25">
                    Lihat rekap payroll
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>

        <div class="rounded-3xl border border-white/70 bg-white/80 p-6 shadow-sm backdrop-blur"><h3 class="text-sm font-extrabold text-stone-800">Sumber Angka</h3><p class="mt-2 text-xs leading-relaxed text-stone-500">KPI harian dihitung dari <strong>jadwal kerja hari ini</strong>, bukan seluruh karyawan aktif. Detail status dipusatkan pada Monitor Absensi agar Dashboard tidak menampilkan fitur yang sama dua kali.</p><a href="{{ route('admin.monitor.index') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700">Buka Monitor <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a></div>
    </div>
</div>
@endsection
