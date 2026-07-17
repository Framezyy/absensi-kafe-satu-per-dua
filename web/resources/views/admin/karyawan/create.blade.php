@extends("layouts.admin")
@section("title", "Tambah Karyawan")
@section("content")
<div class="mx-auto max-w-3xl">
    <a href="{{ route("admin.karyawan.index") }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 transition hover:text-amber-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke daftar
    </a>

    <form method="POST" action="{{ route("admin.karyawan.store") }}" class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur">
        @csrf

        {{-- Section: Data Diri --}}
        <div class="border-b border-stone-100 px-7 py-5">
            <h3 class="text-base font-extrabold text-stone-800">Data Diri Karyawan</h3>
            <p class="text-xs text-stone-400">Informasi identitas dan pekerjaan</p>
        </div>
        <div class="space-y-5 px-7 py-6">
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-stone-700">Nama Lengkap</label>
                <input type="text" name="nama" id="inputNama" value="{{ old("nama") }}" required class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15" placeholder="Contoh: Andi Saputra">
                @error("nama")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-stone-700">Jabatan</label>
                <select name="jabatan" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                    <option>Barista</option><option>Kasir</option><option>Pelayan</option><option>Koki</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-stone-700">Lokasi Kerja</label>
                <select name="lokasi_kerja_id" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                    <option value="">-- Pilih Lokasi --</option>
                    @foreach($lokasi as $l)<option value="{{ $l->id }}">{{ $l->nama_lokasi }}</option>@endforeach
                </select>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-stone-700">Tarif per Hari</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-stone-400">Rp</span>
                        <input type="number" name="tarif_harian" value="{{ old("tarif_harian", 80000) }}" min="0" class="w-full rounded-2xl border border-stone-200 bg-white py-3 pl-10 pr-4 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-stone-700">Tanggal Bergabung</label>
                    <input type="date" name="tanggal_bergabung" value="{{ old("tanggal_bergabung", date("Y-m-d")) }}" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                </div>
            </div>
        </div>

        {{-- Section: Akun Login --}}
        <div class="border-y border-stone-100 bg-amber-50/40 px-7 py-5">
            <h3 class="text-base font-extrabold text-stone-800">Akun Login Karyawan</h3>
            <p class="text-xs text-stone-400">Kredensial untuk masuk ke aplikasi mobile</p>
        </div>
        <div class="space-y-5 px-7 py-6">
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-stone-700">Username</label>
                <input type="text" name="username" id="inputUsername" value="{{ old("username") }}" required class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15" placeholder="andisaputra">
                <p class="mt-1 text-xs text-stone-400">Otomatis dari nama. Huruf kecil, angka, titik, garis bawah.</p>
                @error("username")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-stone-700">Password</label>
                <input type="text" name="password" id="inputPassword" value="{{ old("password") }}" required minlength="12" maxlength="12" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15" placeholder="Tepat 12 karakter">
                <p class="mt-1 text-xs text-stone-400">Wajib 12 karakter. Berikan password ini ke karyawan (sebaiknya beda tiap akun).</p>
                @error("password")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-stone-100 px-7 py-5">
            <a href="{{ route("admin.karyawan.index") }}" class="rounded-2xl border border-stone-200 px-5 py-2.5 text-sm font-semibold text-stone-600 transition hover:bg-stone-50">Batal</a>
            <button type="submit" class="rounded-2xl px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:shadow-amber-500/40" style="background: linear-gradient(135deg, #d97706, #b45309);">Simpan Karyawan</button>
        </div>
    </form>
</div>

@push("scripts")
<script>
document.addEventListener("DOMContentLoaded", function () {
    var nama = document.getElementById("inputNama");
    var username = document.getElementById("inputUsername");
    var pass = document.getElementById("inputPassword");
    var edited = false;
    username.addEventListener("input", function () { edited = true; });
    nama.addEventListener("input", function () {
        if (edited) return;
        username.value = nama.value.toLowerCase().replace(/[^a-z0-9]/g, "");
    });
    username.addEventListener("blur", function () {
        username.value = username.value.toLowerCase().replace(/[^a-z0-9._]/g, "");
    });
    // Password: buang spasi + maksimal 12 karakter.
    if (pass) {
        pass.addEventListener("input", function () {
            this.value = this.value.replace(/\s/g, "").slice(0, 12);
        });
    }
});
</script>
@endpush
@endsection