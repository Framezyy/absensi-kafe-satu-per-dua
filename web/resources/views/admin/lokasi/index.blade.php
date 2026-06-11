@extends("layouts.admin")
@section("title", "Lokasi Kerja")
@section("content")

@push("head")
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

<div class="mb-4">
    <h2 class="text-lg font-semibold">Lokasi Kerja</h2>
    <p class="text-sm text-gray-500">Konfigurasi lokasi geofence untuk absensi karyawan (single-tenant)</p>
</div>

@if($lokasi)
<div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            <tr><th class="px-5 py-3">Nama Lokasi</th><th class="px-5 py-3">Koordinat</th><th class="px-5 py-3">Radius</th><th class="px-5 py-3">Jam Masuk</th><th class="px-5 py-3">Toleransi</th><th class="px-5 py-3">Karyawan</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Aksi</th></tr>
        </thead>
        <tbody>
            <tr>
                <td class="px-5 py-4 font-medium">{{ $lokasi->nama_lokasi }}</td>
                <td class="px-5 py-4 text-gray-500 text-xs font-mono">{{ number_format($lokasi->latitude, 6) }}, {{ number_format($lokasi->longitude, 6) }}</td>
                <td class="px-5 py-4">{{ $lokasi->radius_meter }} m</td>
                <td class="px-5 py-4">{{ \Carbon\Carbon::parse($lokasi->jam_masuk_standar)->format('H:i') }}</td>
                <td class="px-5 py-4">{{ $lokasi->toleransi_menit }} menit</td>
                <td class="px-5 py-4">{{ $lokasi->karyawan_count }} orang</td>
                <td class="px-5 py-4">@if($lokasi->is_aktif)<span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">Aktif</span>@else<span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">Nonaktif</span>@endif</td>
                <td class="px-5 py-4"><button id="btnEdit" class="text-amber-600 hover:text-amber-800 text-sm font-medium" data-id="{{ $lokasi->id }}" data-nama="{{ $lokasi->nama_lokasi }}" data-lat="{{ $lokasi->latitude }}" data-lng="{{ $lokasi->longitude }}" data-radius="{{ $lokasi->radius_meter }}" data-jam="{{ \Carbon\Carbon::parse($lokasi->jam_masuk_standar)->format('H:i') }}" data-toleransi="{{ $lokasi->toleransi_menit }}">✏️ Edit</button></td>
            </tr>
        </tbody>
    </table>
</div>
@else
<div class="rounded-xl bg-white p-8 shadow-sm border border-gray-100 text-center text-gray-400">
    Belum ada lokasi kerja. Jalankan seeder: <code class="bg-gray-100 px-2 py-1 rounded text-xs">php artisan db:seed --class=LokasiKerjaSeeder</code>
</div>
@endif

{{-- MODAL EDIT --}}
<div id="modalEdit" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b px-6 py-4">
            <h3 class="text-lg font-semibold">Edit Lokasi Kerja</h3>
            <button id="btnClose" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <div id="map" class="h-80 w-full rounded-xl border border-gray-200 z-0"></div>
                <div class="mt-2 flex items-center gap-2 text-xs text-gray-500">
                    <span>Klik di peta atau drag marker untuk memilih titik.</span>
                    <button id="btnLokasi" class="rounded-lg bg-amber-100 px-2 py-1 text-amber-700 hover:bg-amber-200">📍 Lokasi Saya</button>
                </div>
            </div>
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Radius Geofence</label>
                <div class="flex items-center gap-4">
                    <input type="range" id="radiusSlider" min="10" max="500" value="50" class="flex-1 accent-amber-600">
                    <span id="radiusValue" class="w-16 rounded-lg bg-gray-100 px-3 py-1.5 text-center text-sm font-mono">50 m</span>
                </div>
            </div>
            <form id="lokasiForm" method="POST" class="space-y-4">
                @csrf
                @method("PUT")
                <input type="hidden" name="latitude" id="inputLat">
                <input type="hidden" name="longitude" id="inputLng">
                <input type="hidden" name="radius_meter" id="inputRadius">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Nama Lokasi</label>
                    <input type="text" name="nama_lokasi" id="inputNama" required class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Jam Masuk Standar</label>
                        <input type="time" name="jam_masuk_standar" id="inputJam" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Toleransi (menit)</label>
                        <input type="number" name="toleransi_menit" id="inputToleransi" min="0" max="60" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    </div>
                </div>
                <div class="text-xs text-gray-400">Koordinat: <span id="coordDisplay" class="font-mono"></span></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" id="btnBatal" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</button>
                    <button type="submit" class="rounded-xl bg-amber-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-amber-800">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push("scripts")
<script>
document.addEventListener("DOMContentLoaded", function() {
    var modal = document.getElementById("modalEdit");
    var btnEdit = document.getElementById("btnEdit");
    var btnClose = document.getElementById("btnClose");
    var btnBatal = document.getElementById("btnBatal");
    var btnLokasi = document.getElementById("btnLokasi");
    var radiusSlider = document.getElementById("radiusSlider");
    var inputNama = document.getElementById("inputNama");
    var inputJam = document.getElementById("inputJam");
    var inputToleransi = document.getElementById("inputToleransi");
    var inputLat = document.getElementById("inputLat");
    var inputLng = document.getElementById("inputLng");
    var inputRadius = document.getElementById("inputRadius");
    var coordDisplay = document.getElementById("coordDisplay");
    var radiusValue = document.getElementById("radiusValue");
    var lokasiForm = document.getElementById("lokasiForm");

    var map, marker, circle;
    var mapInitialized = false;

    if (btnEdit) {
        btnEdit.addEventListener("click", function() {
            var data = this.dataset;
            modal.classList.remove("hidden");
            inputNama.value = data.nama;
            inputJam.value = data.jam;
            inputToleransi.value = data.toleransi;
            inputLat.value = data.lat;
            inputLng.value = data.lng;
            inputRadius.value = data.radius;
            radiusSlider.value = data.radius;
            radiusValue.textContent = data.radius + " m";
            coordDisplay.textContent = parseFloat(data.lat).toFixed(6) + ", " + parseFloat(data.lng).toFixed(6);
            lokasiForm.action = "/admin/lokasi/" + data.id;
            if (!mapInitialized) {
                initMap(parseFloat(data.lat), parseFloat(data.lng));
                mapInitialized = true;
            } else {
                var lat = parseFloat(data.lat), lng = parseFloat(data.lng);
                marker.setLatLng([lat, lng]); circle.setLatLng([lat, lng]);
                circle.setRadius(parseInt(data.radius));
                map.setView([lat, lng], 17);
            }
        });
    }

    function closeModal() { modal.classList.add("hidden"); }
    btnClose.addEventListener("click", closeModal);
    btnBatal.addEventListener("click", closeModal);

    function initMap(lat, lng) {
        map = L.map("map").setView([lat, lng], 17);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>' }).addTo(map);
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        circle = L.circle([lat, lng], { radius: parseInt(inputRadius.value), color: "#d97706", fillColor: "#fbbf24", fillOpacity: 0.15, weight: 2 }).addTo(map);
        map.on("click", function(e) { updatePosition(e.latlng.lat, e.latlng.lng); });
        marker.on("dragend", function() { var p = marker.getLatLng(); updatePosition(p.lat, p.lng); });
    }

    function updatePosition(lat, lng) {
        marker.setLatLng([lat, lng]); circle.setLatLng([lat, lng]);
        inputLat.value = lat.toFixed(6); inputLng.value = lng.toFixed(6);
        coordDisplay.textContent = lat.toFixed(6) + ", " + lng.toFixed(6);
    }

    radiusSlider.addEventListener("input", function() {
        radiusValue.textContent = this.value + " m"; inputRadius.value = this.value; circle.setRadius(parseInt(this.value));
    });

    btnLokasi.addEventListener("click", function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(pos) { updatePosition(pos.coords.latitude, pos.coords.longitude); map.setView([pos.coords.latitude, pos.coords.longitude], 17); },
                function() { alert("Gagal mendapatkan lokasi."); }
            );
        }
    });
});
</script>
@endpush
@endsection