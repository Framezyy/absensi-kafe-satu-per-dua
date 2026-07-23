@extends("layouts.admin")
@section("title", "Analisis Pengujian Wajah")
@section("content")
@php
    $score = $summary['latest_score'];
    $threshold = $summary['verify_threshold'];
    $margin = $score !== null ? round($score - $threshold, 4) : null;
@endphp

<div class="mb-5">
    <p class="text-sm text-stone-500">Penjelasan metode FaceNet, rumus cosine similarity, threshold keputusan, dan statistik deskriptif dari data yang benar-benar tersimpan.</p>
    <div class="mt-3 flex items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50/70 px-5 py-4 text-xs leading-relaxed text-blue-800">
        <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p>Statistik ini bersifat <strong>deskriptif</strong>, bukan nilai akurasi model. Sistem saat ini menyimpan skor verifikasi yang berhasil diteruskan menjadi clock-in, sedangkan percobaan mismatch dan skor clock-out belum menjadi dataset evaluasi.</p>
    </div>
</div>

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
    @foreach([
        ['Karyawan Aktif', $summary['active_employees'], 'populasi enrollment', '#eff6ff', '#2563eb'],
        ['Embedding Valid', $summary['valid_enrollments'], number_format($summary['coverage_percentage'], 2, ',', '.').'% cakupan', '#ecfdf5', '#059669'],
        ['Belum Terdaftar', $summary['not_enrolled'], 'perlu enrollment', '#fff7ed', '#ea580c'],
        ['Sampel Skor', $summary['sample_count'], 'verifikasi tersimpan', '#faf5ff', '#9333ea'],
    ] as $stat)
        <div class="rounded-3xl border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur">
            <div class="text-2xl font-extrabold" style="color: {{ $stat[4] }}">{{ $stat[1] }}</div>
            <div class="mt-1 text-sm font-semibold text-stone-700">{{ $stat[0] }}</div>
            <div class="text-[11px] text-stone-400">{{ $stat[2] }}</div>
            <div class="mt-3 h-1.5 rounded-full" style="background: {{ $stat[3] }}"></div>
        </div>
    @endforeach
</div>

<div class="mb-6 grid gap-5 xl:grid-cols-2">
    <div class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur">
        <div class="border-b border-stone-100 px-6 py-5" style="background: linear-gradient(120deg, #faf5ef, #f5ede3);">
            <h3 class="font-extrabold text-stone-800">1. Pembentukan Embedding</h3>
            <p class="mt-1 text-xs text-stone-500">MTCNN mendeteksi dan menyelaraskan wajah, lalu FaceNet menghasilkan vektor 512 dimensi.</p>
        </div>
        <div class="space-y-4 p-6">
            <div class="overflow-x-auto rounded-2xl bg-stone-900 px-5 py-4 text-center text-sm text-white">
                <span class="font-serif text-lg">e&#772;<sub>j</sub> = (1 / m) &Sigma;<sup>m</sup><sub>k=1</sub> e<sub>kj</sub></span>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                <div class="rounded-2xl bg-stone-50 p-3"><strong class="block text-stone-700">m</strong><span class="text-stone-400">frame valid</span></div>
                <div class="rounded-2xl bg-stone-50 p-3"><strong class="block text-stone-700">j</strong><span class="text-stone-400">komponen 1–512</span></div>
                <div class="rounded-2xl bg-stone-50 p-3"><strong class="block text-stone-700">e&#772;</strong><span class="text-stone-400">mean embedding</span></div>
            </div>
            <p class="text-xs leading-relaxed text-stone-500">Embedding beberapa frame dirata-ratakan per komponen untuk membentuk referensi wajah karyawan. Vektor mentah tidak ditampilkan karena termasuk data biometrik sensitif.</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur">
        <div class="border-b border-stone-100 px-6 py-5" style="background: linear-gradient(120deg, #faf5ef, #f5ede3);">
            <h3 class="font-extrabold text-stone-800">2. Cosine Similarity</h3>
            <p class="mt-1 text-xs text-stone-500">Mengukur kemiripan arah embedding referensi A dan embedding wajah saat verifikasi B.</p>
        </div>
        <div class="space-y-4 p-6">
            <div class="overflow-x-auto rounded-2xl bg-stone-900 px-5 py-4 text-center text-white">
                <div class="font-serif text-base">cos(A,B) = <span class="inline-block border-b border-white px-2">&Sigma;<sup>512</sup><sub>i=1</sub> a<sub>i</sub>b<sub>i</sub></span></div>
                <div class="mt-1 font-serif text-sm">&radic;(&Sigma;a<sub>i</sub><sup>2</sup>) &times; &radic;(&Sigma;b<sub>i</sub><sup>2</sup>)</div>
            </div>
            <div class="space-y-2 text-xs text-stone-600">
                <div class="flex items-center justify-between rounded-xl bg-stone-50 px-4 py-2.5"><span>Dot product</span><code>&Sigma; a<sub>i</sub>b<sub>i</sub></code></div>
                <div class="flex items-center justify-between rounded-xl bg-stone-50 px-4 py-2.5"><span>Norma embedding</span><code>&radic;(&Sigma; x<sub>i</sub><sup>2</sup>)</code></div>
                <div class="flex items-center justify-between rounded-xl bg-stone-50 px-4 py-2.5"><span>Dimensi model</span><code>n = 512</code></div>
            </div>
        </div>
    </div>
</div>

<div class="mb-6 grid gap-5 xl:grid-cols-5">
    <div class="rounded-3xl border border-white/70 bg-white/80 p-6 shadow-sm xl:col-span-2">
        <h3 class="font-extrabold text-stone-800">Threshold Sistem</h3>
        <div class="mt-4 space-y-3">
            <div class="rounded-2xl border border-green-100 bg-green-50 p-4"><div class="flex justify-between"><span class="text-sm font-bold text-green-800">Verifikasi Absensi 1:1</span><strong class="font-mono text-green-700">{{ number_format($summary['verify_threshold'], 2, ',', '.') }}</strong></div><p class="mt-1 text-xs text-green-700">Match jika similarity &ge; threshold.</p></div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4"><div class="flex justify-between"><span class="text-sm font-bold text-amber-800">Screening Duplikat 1:N</span><strong class="font-mono text-amber-700">{{ number_format($summary['duplicate_threshold'], 2, ',', '.') }}</strong></div><p class="mt-1 text-xs text-amber-700">Lebih sensitif untuk mencegah satu wajah didaftarkan pada akun lain.</p></div>
        </div>
    </div>

    <div class="rounded-3xl bg-stone-800 p-6 text-white shadow-lg xl:col-span-3">
        <div class="text-xs font-bold uppercase tracking-wider text-amber-300">Contoh Substitusi Data Aktual</div>
        @if($score !== null)
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl bg-white/10 p-4"><div class="text-xs text-stone-300">Similarity</div><div class="mt-1 font-mono text-xl font-bold">s = {{ number_format($score, 4, ',', '.') }}</div></div>
                <div class="rounded-2xl bg-white/10 p-4"><div class="text-xs text-stone-300">Threshold</div><div class="mt-1 font-mono text-xl font-bold">T = {{ number_format($threshold, 4, ',', '.') }}</div></div>
                <div class="rounded-2xl bg-white/10 p-4"><div class="text-xs text-stone-300">Margin</div><div class="mt-1 font-mono text-xl font-bold">m = {{ number_format($margin, 4, ',', '.') }}</div></div>
            </div>
            <div class="mt-4 rounded-2xl bg-amber-500/15 px-5 py-4 font-mono text-sm text-amber-200">{{ number_format($score, 4, ',', '.') }} &ge; {{ number_format($threshold, 4, ',', '.') }} &rArr; MATCH &nbsp; | &nbsp; margin = {{ number_format($score, 4, ',', '.') }} - {{ number_format($threshold, 4, ',', '.') }} = {{ number_format($margin, 4, ',', '.') }}</div>
            <p class="mt-3 text-xs leading-relaxed text-stone-300">Skor berasal dari <code>absensi.face_similarity_score</code>. Probe embedding tidak disimpan, sehingga halaman menunjukkan substitusi aturan keputusan, bukan merekonstruksi dot product kejadian.</p>
        @else
            <div class="mt-5 rounded-2xl bg-white/10 p-5 text-sm text-stone-300">Belum ada skor verifikasi tersimpan untuk dijadikan contoh substitusi.</div>
        @endif
    </div>
</div>

<div class="mb-6 overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm">
    <div class="flex flex-col gap-2 border-b border-stone-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="font-extrabold text-stone-800">Distribusi Skor Tersimpan</h3><p class="text-xs text-stone-400">N = {{ $summary['sample_count'] }} verifikasi berhasil yang diteruskan menjadi absensi.</p></div><div class="flex gap-2 text-xs"><span class="rounded-full bg-stone-100 px-3 py-1.5">Min: {{ $summary['minimum_score'] !== null ? number_format($summary['minimum_score'], 4, ',', '.') : '-' }}</span><span class="rounded-full bg-amber-50 px-3 py-1.5 text-amber-700">Mean: {{ $summary['average_score'] !== null ? number_format($summary['average_score'], 4, ',', '.') : '-' }}</span><span class="rounded-full bg-green-50 px-3 py-1.5 text-green-700">Max: {{ $summary['maximum_score'] !== null ? number_format($summary['maximum_score'], 4, ',', '.') : '-' }}</span></div></div>
    <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b border-stone-100 text-left text-[11px] font-bold uppercase tracking-wider text-stone-400"><th class="px-6 py-4">Tanggal / Karyawan</th><th class="px-6 py-4">Shift</th><th class="px-6 py-4">Similarity</th><th class="px-6 py-4">Threshold</th><th class="px-6 py-4">Margin</th><th class="px-6 py-4">Keputusan</th></tr></thead><tbody class="divide-y divide-stone-50">
        @forelse($verifications as $row)<tr class="hover:bg-amber-50/40"><td class="px-6 py-4"><div class="font-semibold text-stone-800">{{ $row->attendance->karyawan?->nama_lengkap ?? 'Data karyawan tidak tersedia' }}</div><div class="text-[11px] text-stone-400">{{ $row->attendance->clock_in_at?->translatedFormat('d M Y, H:i') ?? $row->attendance->tanggal?->translatedFormat('d M Y') }}</div></td><td class="px-6 py-4 text-xs text-stone-500">{{ $row->attendance->jadwalKerja?->shift?->nama ?? 'Legacy' }}</td><td class="px-6 py-4 font-mono font-bold text-stone-800">{{ number_format($row->score, 4, ',', '.') }}<div class="text-[10px] font-normal text-stone-400">{{ number_format($row->score * 100, 2, ',', '.') }}%</div></td><td class="px-6 py-4 font-mono text-stone-500">{{ number_format($threshold, 4, ',', '.') }}</td><td class="px-6 py-4 font-mono {{ $row->margin >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ $row->margin >= 0 ? '+' : '' }}{{ number_format($row->margin, 4, ',', '.') }}</td><td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $row->is_consistent ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600' }}">{{ $row->decision }}</span></td></tr>
        @empty<tr><td colspan="6" class="px-6 py-14 text-center text-sm text-stone-400">Belum ada skor verifikasi wajah yang tersimpan.</td></tr>@endforelse
    </tbody></table></div>
</div>

<div class="mb-6 overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm"><div class="border-b border-stone-100 px-6 py-5"><h3 class="font-extrabold text-stone-800">Kualitas Enrollment</h3><p class="text-xs text-stone-400">Menilai struktur embedding tanpa membuka nilai vektor biometrik.</p></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b border-stone-100 text-left text-[11px] font-bold uppercase tracking-wider text-stone-400"><th class="px-6 py-4">Karyawan</th><th class="px-6 py-4">Status</th><th class="px-6 py-4">Dimensi</th><th class="px-6 py-4">Kualitas Struktur</th><th class="px-6 py-4">Registrasi</th><th class="px-6 py-4">Foto Referensi</th></tr></thead><tbody class="divide-y divide-stone-50">@forelse($summary['embeddings'] as $embedding)<tr><td class="px-6 py-4 font-semibold text-stone-800">{{ $embedding->karyawan?->nama_lengkap ?? 'Tidak tersedia' }}</td><td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $embedding->is_aktif ? 'bg-green-50 text-green-700' : 'bg-stone-100 text-stone-500' }}">{{ $embedding->is_aktif ? 'Aktif' : 'Nonaktif' }}</span></td><td class="px-6 py-4 font-mono text-stone-600">{{ $embedding->dimension }}D</td><td class="px-6 py-4 text-xs text-stone-600">{{ $embedding->quality }}</td><td class="px-6 py-4 text-xs text-stone-500">{{ $embedding->registered_at?->translatedFormat('d M Y') ?? '-' }}</td><td class="px-6 py-4 text-xs text-stone-500">{{ $embedding->has_reference_photo ? 'Tersedia' : 'Tidak tersedia' }}</td></tr>@empty<tr><td colspan="6" class="px-6 py-14 text-center text-sm text-stone-400">Belum ada embedding wajah.</td></tr>@endforelse</tbody></table></div></div>

<div class="rounded-3xl border border-red-100 bg-red-50/60 p-6"><h3 class="font-extrabold text-red-800">Batasan Interpretasi</h3><div class="mt-4 grid gap-3 text-xs leading-relaxed text-red-700 md:grid-cols-2"><p>1. Percobaan mismatch belum disimpan, sehingga acceptance rate dan false rejection rate belum dapat dihitung.</p><p>2. Tidak ada label ground truth genuine/impostor, sehingga accuracy, precision, recall, FAR, dan FRR tidak boleh disimpulkan.</p><p>3. Skor pada tabel adalah skor clock-in. Skor clock-out belum disimpan sebagai event terpisah.</p><p>4. Threshold {{ number_format($threshold, 2, ',', '.') }} belum dikalibrasi menggunakan ROC/EER dari dataset lokal karyawan kafe.</p></div></div>
@endsection
