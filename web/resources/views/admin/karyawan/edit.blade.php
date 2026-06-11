@extends("layouts.admin")
@section("title", "Edit Karyawan")
@section("content")
<div class="max-w-2xl">
    <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
        <h3 class="text-lg font-semibold mb-6">Edit Karyawan: {{ $k->nama_lengkap }}</h3>
        <form method="POST" action="{{ route("admin.karyawan.update", $k->id) }}" class="space-y-4">
            @csrf
            @method("PUT")
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700">Nama Lengkap</label><input type="text" name="nama" value="{{ $k->nama_lengkap }}" required class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"></div>
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700">NIK</label><input type="text" value="{{ $k->nik }}" readonly class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-500"></div>
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700">Jabatan</label><select name="jabatan" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"><option {{ $k->jabatan == "Barista" ? "selected" : "" }}>Barista</option><option {{ $k->jabatan == "Kasir" ? "selected" : "" }}>Kasir</option><option {{ $k->jabatan == "Pelayan" ? "selected" : "" }}>Pelayan</option><option {{ $k->jabatan == "Koki" ? "selected" : "" }}>Koki</option></select></div>
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700">Lokasi Kerja</label><select name="lokasi_kerja_id" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"><option value="">-- Pilih Lokasi --</option>@foreach($lokasi as $l)<option value="{{ $l->id }}" {{ $k->lokasi_kerja_id == $l->id ? "selected" : "" }}>{{ $l->nama_lokasi }}</option>@endforeach</select></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="mb-1.5 block text-sm font-medium text-gray-700">Tarif per Hari (Rp)</label><input type="number" name="tarif_harian" value="{{ $k->tarif_gaji_harian }}" min="0" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"></div>
                <div><label class="mb-1.5 block text-sm font-medium text-gray-700">Status</label><select name="status" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"><option value="aktif" {{ $k->status == "aktif" ? "selected" : "" }}>Aktif</option><option value="nonaktif" {{ $k->status == "nonaktif" ? "selected" : "" }}>Nonaktif</option></select></div>
            </div>
            <div class="flex gap-3 pt-2"><a href="{{ route("admin.karyawan.index") }}" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a><button type="submit" class="rounded-xl bg-amber-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-amber-800">Perbarui</button></div>
        </form>
    </div>
</div>
@endsection