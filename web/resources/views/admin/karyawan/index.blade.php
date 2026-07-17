@extends("layouts.admin")
@section("title", "Manajemen Karyawan")
@section("content")

@php
    $aktif = $karyawan->where("status", "aktif")->count();
    $enrolled = $karyawan->filter(fn($k) => $k->faceEmbedding && $k->faceEmbedding->is_aktif)->count();
@endphp

{{-- Kartu konfirmasi kredensial (muncul sekali setelah akun dibuat) --}}
@if(session("new_credential"))
    @php $cred = session("new_credential"); @endphp
    <div x-data="{ show: true }" x-show="show" class="mb-5 overflow-hidden rounded-3xl border border-green-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-green-100 bg-green-50/70 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-sm font-extrabold text-stone-800">Akun karyawan berhasil dibuat</div>
                    <div class="text-xs text-stone-500">Catat & berikan kredensial ini ke <span class="font-semibold">{{ $cred["nama"] }}</span>. Password tidak bisa dilihat lagi setelah halaman ini ditutup.</div>
                </div>
            </div>
            <button @click="show = false" class="flex h-8 w-8 items-center justify-center rounded-xl text-stone-400 transition hover:bg-stone-100 hover:text-stone-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="grid gap-4 px-6 py-5 sm:grid-cols-2"
             x-data="{ copied: false, copyAll() { navigator.clipboard.writeText('Username: {{ $cred["username"] }}\nPassword: {{ $cred["password"] }}'); this.copied = true; setTimeout(() => this.copied = false, 2000); } }">
            <div class="rounded-2xl bg-stone-50 px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wide text-stone-400">Username</div>
                <div class="mt-0.5 font-mono text-base font-bold text-stone-800">{{ $cred["username"] }}</div>
            </div>
            <div class="rounded-2xl bg-stone-50 px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wide text-stone-400">Password</div>
                <div class="mt-0.5 font-mono text-base font-bold text-stone-800">{{ $cred["password"] }}</div>
            </div>
            <div class="sm:col-span-2">
                <button type="button" @click="copyAll()" class="flex items-center gap-2 rounded-xl bg-amber-100 px-4 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-200">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <span x-text="copied ? 'Tersalin!' : 'Salin Kredensial'"></span>
                </button>
            </div>
        </div>
    </div>
@endif

{{-- Toolbar --}}
<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between" x-data="{ q: '' }">
    <div class="flex flex-wrap items-center gap-3">
        <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 shadow-sm backdrop-blur">
            <span class="text-lg font-extrabold text-stone-800">{{ $karyawan->count() }}</span>
            <span class="ml-1 text-xs text-stone-400">total</span>
        </div>
        <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 shadow-sm backdrop-blur">
            <span class="text-lg font-extrabold text-green-600">{{ $aktif }}</span>
            <span class="ml-1 text-xs text-stone-400">aktif</span>
        </div>
        <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-2.5 shadow-sm backdrop-blur">
            <span class="text-lg font-extrabold text-amber-600">{{ $enrolled }}</span>
            <span class="ml-1 text-xs text-stone-400">wajah terdaftar</span>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <div class="relative">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input x-model="q" type="text" placeholder="Cari karyawan..." class="w-52 rounded-2xl border border-stone-200 bg-white/80 py-2.5 pl-10 pr-4 text-sm outline-none backdrop-blur transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
        </div>
        <a href="{{ route("admin.karyawan.create") }}" class="flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:shadow-amber-500/40" style="background: linear-gradient(135deg, #d97706, #b45309);">
            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah
        </a>
    </div>

    {{-- Data untuk filter JS --}}
    <script>window._karyawanFilter = function(){};</script>
</div>

{{-- Tabel + modal hapus global (di root supaya tidak terjebak backdrop-blur card) --}}
<div x-data="{ confirmDelete: false, delId: null, delNama: '', delAction: '', openDelete(id, nama) { this.delId = id; this.delNama = nama; this.delAction = '/admin/karyawan/' + id; this.confirmDelete = true; } }">
    <div class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-stone-100 text-left text-[11px] font-bold uppercase tracking-wider text-stone-400">
                    <th class="px-6 py-4">Karyawan</th>
                    <th class="px-6 py-4">Username</th>
                    <th class="px-6 py-4">Jabatan</th>
                    <th class="px-6 py-4">Tarif/Hari</th>
                    <th class="px-6 py-4">Wajah</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-50">
                @foreach($karyawan as $k)
                    <tr class="transition hover:bg-amber-50/40">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl text-xs font-bold text-white" style="background: linear-gradient(135deg, #f59e0b, #d97706);">{{ strtoupper(substr($k->nama_lengkap, 0, 2)) }}</div>
                                <div>
                                    <div class="font-semibold text-stone-800">{{ $k->nama_lengkap }}</div>
                                    <div class="font-mono text-[11px] text-stone-400">{{ $k->kode_karyawan }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4"><span class="rounded-lg bg-stone-100 px-2.5 py-1 font-mono text-xs text-stone-600">{{ $k->user->username ?? "-" }}</span></td>
                        <td class="px-6 py-4 text-stone-600">{{ $k->jabatan }}</td>
                        <td class="px-6 py-4 font-semibold text-stone-700">Rp {{ number_format($k->tarif_gaji_harian, 0, ",", ".") }}</td>
                        <td class="px-6 py-4">
                            @if($k->faceEmbedding && $k->faceEmbedding->is_aktif)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-[11px] font-semibold text-green-700"><span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>Terdaftar</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-stone-100 px-2.5 py-1 text-[11px] font-semibold text-stone-500"><span class="h-1.5 w-1.5 rounded-full bg-stone-400"></span>Belum</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($k->status === "aktif")
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700"><span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>Aktif</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-600"><span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route("admin.karyawan.edit", $k->id) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-600 transition hover:bg-amber-100 hover:text-amber-700">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit
                                </a>
                                <button type="button" @click="openDelete({{ $k->id }}, '{{ addslashes($k->nama_lengkap) }}')" class="inline-flex items-center gap-1.5 rounded-xl bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-600 transition hover:bg-red-100 hover:text-red-600">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
                @if($karyawan->isEmpty())
                    <tr><td colspan="7" class="px-6 py-16 text-center text-sm text-stone-400">Belum ada karyawan terdaftar.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
    </div>

    {{-- Modal konfirmasi hapus (global, di root wrapper supaya tidak terjebak backdrop-blur card) --}}
    <div x-show="confirmDelete" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 p-4 backdrop-blur-sm" @click.self="confirmDelete = false" @keydown.escape.window="confirmDelete = false">
        <div x-show="confirmDelete" x-transition class="w-full max-w-md rounded-3xl bg-white p-6 text-left shadow-2xl">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-red-50">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.71-3L13.71 4a2 2 0 00-3.42 0L3.36 16a2 2 0 001.71 3z"/></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-extrabold text-stone-800">Hapus karyawan?</h3>
                    <p class="mt-1 text-sm text-stone-500">Akun <span class="font-semibold text-stone-700" x-text="delNama"></span> beserta seluruh data absensi, wajah, izin, bonus, dan penggajiannya akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="confirmDelete = false" class="rounded-2xl border border-stone-200 px-5 py-2.5 text-sm font-semibold text-stone-600 transition hover:bg-stone-50">Batal</button>
                <form method="POST" :action="delAction">
                    @csrf
                    @method("DELETE")
                    <button type="submit" class="rounded-2xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-500/25 transition hover:bg-red-700">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection