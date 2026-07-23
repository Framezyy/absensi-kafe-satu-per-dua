@extends("layouts.admin")
@section("title", "Edit Karyawan")
@section("content")
<div class="mx-auto max-w-3xl">
    <a href="{{ route("admin.karyawan.index") }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 transition hover:text-amber-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke daftar
    </a>

    @if($errors->any())
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-3.5 text-sm text-red-700">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <ul class="list-disc space-y-0.5 pl-4">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route("admin.karyawan.update", $k->id) }}" class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur">
        @csrf
        @method("PUT")

        <div class="flex items-center gap-4 border-b border-stone-100 px-7 py-5">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl text-lg font-bold text-white" style="background: linear-gradient(135deg, #f59e0b, #d97706);">{{ strtoupper(substr($k->nama_lengkap, 0, 2)) }}</div>
            <div>
                <h3 class="text-base font-extrabold text-stone-800">{{ $k->nama_lengkap }}</h3>
                <p class="font-mono text-xs text-stone-400">&#64;{{ $k->user->username ?? "-" }} • {{ $k->kode_karyawan }}</p>
            </div>
        </div>

        {{-- Section: Data Diri --}}
        <div class="border-b border-stone-100 px-7 py-4">
            <h4 class="text-sm font-extrabold text-stone-800">Data Diri & Pekerjaan</h4>
        </div>
        <div class="space-y-5 px-7 py-6">
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-stone-700">Nama Lengkap</label>
                <input type="text" name="nama" value="{{ old('nama', $k->nama_lengkap) }}" required class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-stone-700">ID Karyawan <span class="font-normal text-stone-400">(otomatis)</span></label>
                <input type="text" value="{{ $k->kode_karyawan }}" readonly class="w-full cursor-not-allowed rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm font-mono text-stone-500">
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-stone-700">Jabatan</label>
                    <select name="jabatan" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                        <option {{ $k->jabatan == "Barista" ? "selected" : "" }}>Barista</option>
                        <option {{ $k->jabatan == "Kasir" ? "selected" : "" }}>Kasir</option>
                        <option {{ $k->jabatan == "Pelayan" ? "selected" : "" }}>Pelayan</option>
                        <option {{ $k->jabatan == "Koki" ? "selected" : "" }}>Koki</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-stone-700">Lokasi Default</label>
                    <select name="lokasi_kerja_id" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                        <option value="">-- Pilih Lokasi --</option>
                        @foreach($lokasi as $l)<option value="{{ $l->id }}" {{ $k->lokasi_kerja_id == $l->id ? "selected" : "" }}>{{ $l->nama_lokasi }}</option>@endforeach
                    </select>
                    <p class="mt-1 text-xs text-stone-400">Lokasi aktual absensi mengikuti Jadwal Kerja.</p>
                </div>
            </div>
            <div><label class="mb-2 block text-sm font-semibold text-stone-700">Shift Kerja Tetap</label><div class="grid gap-3 sm:grid-cols-2">@foreach($shifts as $shift)<label class="cursor-pointer rounded-2xl border border-stone-200 bg-white p-4 transition has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50"><input type="radio" name="default_shift_id" value="{{ $shift->id }}" class="sr-only" {{ old('default_shift_id',$k->default_shift_id)==$shift->id?'checked':'' }} required><div class="font-bold text-stone-800">Shift {{ $shift->nama }}</div><div class="font-mono text-sm text-stone-500">{{ substr($shift->jam_mulai,0,5) }}–{{ substr($shift->jam_selesai,0,5) }}</div></label>@endforeach</div>@error('default_shift_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="rounded-2xl border border-amber-100 bg-amber-50/60 px-4 py-3">
                    <div class="text-xs font-bold uppercase tracking-wide text-amber-700">Tarif Sistem</div>
                    <div class="mt-1 text-lg font-extrabold text-stone-800">Rp {{ number_format(config('payroll.hourly_rate'), 0, ',', '.') }} <span class="text-xs font-medium text-stone-400">/ jam</span></div>
                    <p class="text-[11px] text-stone-500">Tarif tidak diatur per karyawan.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-stone-700">Status</label>
                    <select name="status" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                        <option value="aktif" {{ $k->status == "aktif" ? "selected" : "" }}>Aktif</option>
                        <option value="nonaktif" {{ $k->status == "nonaktif" ? "selected" : "" }}>Nonaktif</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Section: Akun Login --}}
        <div class="border-y border-stone-100 bg-amber-50/40 px-7 py-4">
            <h4 class="text-sm font-extrabold text-stone-800">Akun Login</h4>
            <p class="text-xs text-stone-400">Ubah username atau reset password karyawan</p>
        </div>
        <div class="space-y-5 px-7 py-6">
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-stone-700">Username</label>
                <input type="text" name="username" id="inputUsername" value="{{ old('username', $k->user->username ?? '') }}" required class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                <p class="mt-1 text-xs text-stone-400">Huruf kecil, angka, titik, garis bawah.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-stone-700">Reset Password <span class="font-normal text-stone-400">(opsional)</span></label>
                <input type="text" name="password" id="inputPassword" value="{{ old('password') }}" minlength="12" maxlength="12" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15" placeholder="Kosongkan jika tidak ingin mengubah">
                <p class="mt-1 text-xs text-stone-400">Isi hanya jika ingin ganti password (tepat 12 karakter). Password lama tidak bisa dilihat.</p>
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-stone-100 px-7 py-5">
            <a href="{{ route("admin.karyawan.index") }}" class="rounded-2xl border border-stone-200 px-5 py-2.5 text-sm font-semibold text-stone-600 transition hover:bg-stone-50">Batal</a>
            <button type="submit" class="rounded-2xl px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:shadow-amber-500/40" style="background: linear-gradient(135deg, #d97706, #b45309);">Perbarui</button>
        </div>
    </form>
</div>

@push("scripts")
<script>
document.addEventListener("DOMContentLoaded", function () {
    var username = document.getElementById("inputUsername");
    var pass = document.getElementById("inputPassword");
    if (username) {
        username.addEventListener("blur", function () {
            this.value = this.value.toLowerCase().replace(/[^a-z0-9._]/g, "");
        });
    }
    // Password: buang spasi + maksimal 12 karakter.
    if (pass) {
        pass.addEventListener("input", function () {
            this.value = this.value.replace(/\s/g, "").slice(0, 12);
        });
    }
});
</script>
@endpush
</div>
@endsection
