@extends("layouts.admin")
@section("title", "Tambah Karyawan")
@section("content")
<div class="max-w-2xl">
    <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
        <h3 class="text-lg font-semibold mb-6">Tambah Karyawan Baru</h3>
        <form method="POST" action="{{ route("admin.karyawan.store") }}" class="space-y-4">
            @csrf
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700">Nama Lengkap</label><input type="text" name="nama" value="{{ old("nama") }}" required class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">@error('nama')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror</div>
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700">NIK</label><input type="text" name="nik" value="{{ old("nik") }}" required class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">@error('nik')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror</div>
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700">Jabatan</label><select name="jabatan" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"><option>Barista</option><option>Kasir</option><option>Pelayan</option><option>Koki</option></select></div>
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700">Lokasi Kerja</label><select name="lokasi_kerja_id" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"><option value="">-- Pilih Lokasi --</option>@foreach($lokasi as $l)<option value="{{ $l->id }}">{{ $l->nama_lokasi }}</option>@endforeach</select></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="mb-1.5 block text-sm font-medium text-gray-700">Tarif per Hari (Rp)</label><input type="number" name="tarif_harian" value="{{ old("tarif_harian", 80000) }}" min="0" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"></div>
                <div><label class="mb-1.5 block text-sm font-medium text-gray-700">Tanggal Bergabung</label><input type="date" name="tanggal_bergabung" value="{{ old("tanggal_bergabung", date("Y-m-d")) }}" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"></div>
            </div>
            <div class="flex gap-3 pt-2"><a href="{{ route("admin.karyawan.index") }}" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a><button type="submit" class="rounded-xl bg-amber-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-amber-800">Simpan</button></div>
        </form>
    </div>
</div>
@endsection