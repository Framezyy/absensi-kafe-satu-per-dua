@extends("layouts.admin")
@section("title", "Lokasi Kerja")
@section("content")

@push("head")
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

<p class="mb-5 text-sm text-stone-500">Konfigurasi lokasi geofence untuk validasi absensi karyawan</p>

@if($lokasi)
<div class="overflow-hidden rounded-3xl border border-white/70 bg-white/80 shadow-sm backdrop-blur">
    {{-- Header lokasi --}}
    <div class="flex flex-col gap-4 border-b border-stone-100 p-6 sm:flex-row sm:items-center sm:justify-between" style="background: linear-gradient(120deg, #faf5ef, #f5ede3);">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl shadow-lg" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-extrabold text-stone-800">{{ $lokasi->nama_lokasi }}</h3>
                    @if($lokasi->is_aktif)<span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-[11px] font-semibold text-green-700"><span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>Aktif</span>@endif
                </div>
                <div class="mt-0.5 font-mono text-xs text-stone-400">{{ number_format($lokasi->latitude, 6) }}, {{ number_format($lokasi->longitude, 6) }}</div>
            </div>
        </div>
        <button id="btnEdit" class="flex items-center gap-2 self-start rounded-2xl px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:shadow-amber-500/40 sm:self-auto" style="background: linear-gradient(135deg, #d97706, #b45309);"
            data-id="{{ $lokasi->id }}" data-nama="{{ $lokasi->nama_lokasi }}" data-lat="{{ $lokasi->latitude }}" data-lng="{{ $lokasi->longitude }}" data-radius="{{ $lokasi->radius_meter }}" data-jam="{{ \Carbon\Carbon::parse($lokasi->jam_masuk_standar)->format('H:i') }}" data-toleransi="{{ $lokasi->toleransi_menit }}">
            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit Lokasi
        </button>
    </div>

    {{-- Detail grid --}}
    <div class="grid grid-cols-2 gap-px bg-stone-100 sm:grid-cols-4">
        @php
            $details = [
                ["Radius Geofence", $lokasi->radius_meter . " meter", "M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"],
                ["Jam Masuk", \Carbon\Carbon::parse($lokasi->jam_masuk_standar)->format("H:i") . " WIB", "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"],
                ["Toleransi", $lokasi->toleransi_menit . " menit", "M13 10V3L4 14h7v7l9-11h-7z"],
                ["Karyawan", $lokasi->karyawan_count . " orang", "M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2.13a4 4 0 100-8 4 4 0 000 8z"],
            ];
        @endphp
        @foreach($details as $d)
            <div class="bg-white p-5">
                <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50">
                    <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $d[2] }}"/></svg>
                </div>
                <div class="text-lg font-extrabold text-stone-800">{{ $d[1] }}</div>
                <div class="text-xs text-stone-400">{{ $d[0] }}</div>
            </div>
        @endforeach
    </div>
</div>
@else
<div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-stone-200 bg-white/50 py-16 text-center">
    <svg class="h-14 w-14 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    <p class="mt-3 text-sm font-medium text-stone-400">Belum ada lokasi kerja.</p>
    <code class="mt-2 rounded-lg bg-stone-100 px-3 py-1.5 text-xs text-stone-600">php artisan db:seed --class=LokasiKerjaSeeder</code>
</div>
@endif

{{-- MODAL EDIT --}}
<div id="modalEdit" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-stone-900/50 p-4 backdrop-blur-sm">
    <div class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
            <h3 class="text-lg font-extrabold text-stone-800">Edit Lokasi Kerja</h3>
            <button id="btnClose" class="flex h-9 w-9 items-center justify-center rounded-xl text-stone-400 transition hover:bg-stone-100 hover:text-stone-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <div id="map" class="z-0 h-80 w-full rounded-2xl border border-stone-200"></div>
                <div class="mt-2 flex items-center gap-2 text-xs text-stone-500">
                    <span>Klik di peta atau geser marker untuk memilih titik.</span>
                    <button id="btnLokasi" class="rounded-lg bg-amber-100 px-2.5 py-1 font-semibold text-amber-700 transition hover:bg-amber-200">📍 Lokasi Saya</button>
                </div>
            </div>
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-semibold text-stone-700">Radius Geofence</label>
                <div class="flex items-center gap-4">
                    <input type="range" id="radiusSlider" min="10" max="500" value="50" class="flex-1 accent-amber-600">
                    <span id="radiusValue" class="w-20 rounded-xl bg-stone-100 px-3 py-1.5 text-center font-mono text-sm font-semibold text-stone-700">50 m</span>
                </div>
            </div>
            <form id="lokasiForm" method="POST" class="space-y-4">
                @csrf
                @method("PUT")
                <input type="hidden" name="latitude" id="inputLat">
                <input type="hidden" name="longitude" id="inputLng">
                <input type="hidden" name="radius_meter" id="inputRadius">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-stone-700">Nama Lokasi</label>
                    <input type="text" name="nama_lokasi" id="inputNama" required class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-stone-700">Jam Masuk Standar</label>
                        <input type="time" name="jam_masuk_standar" id="inputJam" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-stone-700">Toleransi (menit)</label>
                        <input type="number" name="toleransi_menit" id="inputToleransi" min="0" max="60" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15">
                    </div>
                </div>
                <div class="text-xs text-stone-400">Koordinat: <span id="coordDisplay" class="font-mono"></span></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" id="btnBatal" class="rounded-2xl border border-stone-200 px-5 py-2.5 text-sm font-semibold text-stone-600 transition hover:bg-stone-50">Batal</button>
                    <button type="submit" class="rounded-2xl px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:shadow-amber-500/40" style="background: linear-gradient(135deg, #d97706, #b45309);">Simpan Perubahan</button>
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
                setTimeout(function() {
                    initMap(parseFloat(data.lat), parseFloat(data.lng));
                    mapInitialized = true;
                }, 100);
            } else {
                var lat = parseFloat(data.lat), lng = parseFloat(data.lng);
                marker.setLatLng([lat, lng]); circle.setLatLng([lat, lng]);
                circle.setRadius(parseInt(data.radius));
                map.setView([lat, lng], 17);
                setTimeout(function() { map.invalidateSize(); }, 100);
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
        setTimeout(function() { map.invalidateSize(); }, 150);
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