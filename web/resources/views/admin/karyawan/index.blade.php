@extends("layouts.admin")
@section("title", "Manajemen Karyawan")
@section("content")
<div class="mb-4 flex items-center justify-between">
    <h2 class="text-lg font-semibold">Daftar Karyawan</h2>
    <a href="{{ route("admin.karyawan.create") }}" class="rounded-xl bg-amber-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-800 transition">+ Tambah Karyawan</a>
</div>
<div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            <tr><th class="px-5 py-3">Nama</th><th class="px-5 py-3">NIK</th><th class="px-5 py-3">Jabatan</th><th class="px-5 py-3">Tarif/Hari</th><th class="px-5 py-3">Bergabung</th><th class="px-5 py-3">Wajah</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Aksi</th></tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
        @foreach($karyawan as $k)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-4 font-medium">{{ $k->nama_lengkap }}</td>
                <td class="px-5 py-4 text-gray-500 font-mono text-xs">{{ $k->nik }}</td>
                <td class="px-5 py-4">{{ $k->jabatan }}</td>
                <td class="px-5 py-4">Rp {{ number_format($k->tarif_gaji_harian, 0, ',', '.') }}</td>
                <td class="px-5 py-4 text-gray-500">{{ $k->tgl_bergabung->format('d M Y') }}</td>
                <td class="px-5 py-4">@if($k->faceEmbedding && $k->faceEmbedding->is_aktif)<span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Terdaftar</span>@else<span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Belum</span>@endif</td>
                <td class="px-5 py-4">@if($k->status === 'aktif')<span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Aktif</span>@else<span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Nonaktif</span>@endif</td>
                <td class="px-5 py-4"><a href="{{ route("admin.karyawan.edit", $k->id) }}" class="text-amber-600 hover:text-amber-800 text-xs font-medium">Edit</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection