@extends("layouts.admin")
@section("title", "Tambah Bonus")
@section("content")
<div class="max-w-2xl">
    <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
        <h3 class="text-lg font-semibold mb-6">Tambah Bonus</h3>
        <form method="POST" action="{{ route("admin.bonus.store") }}" class="space-y-4">
            @csrf
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700">Karyawan</label><select name="karyawan_id" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">@foreach($karyawan as $k)<option value="{{ $k->id }}">{{ $k->nama_lengkap }} — {{ $k->jabatan }}</option>@endforeach</select></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="mb-1.5 block text-sm font-medium text-gray-700">Periode</label><input type="text" name="periode" value="{{ now()->format('F Y') }}" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"></div>
                <div><label class="mb-1.5 block text-sm font-medium text-gray-700">Jumlah (Rp)</label><input type="number" name="jumlah" value="100000" min="0" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"></div>
            </div>
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700">Keterangan</label><textarea name="keterangan" rows="3" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none" placeholder="Alasan pemberian bonus..."></textarea></div>
            <div class="flex gap-3 pt-2"><a href="{{ route("admin.bonus.index") }}" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a><button type="submit" class="rounded-xl bg-amber-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-amber-800">Simpan</button></div>
        </form>
    </div>
</div>
@endsection