@props(["route", "label", "icon", "active" => false])

@php
    $icons = [
        "grid" => "M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z",
        "activity" => "M22 12h-4l-3 9L9 3l-3 9H2",
        "users" => "M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2.13a4 4 0 100-8 4 4 0 000 8zm6 0a4 4 0 00-3-3.87",
        "map-pin" => "M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z",
        "clipboard-check" => "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4",
        "clock" => "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z",
        "calendar" => "M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z",
        "clock-edit" => "M12 8v4l2 2m5.5-1.5A8 8 0 104 17.5M16 19l4-4 2 2-4 4h-2v-2z",
        "gift" => "M5 8a2 2 0 012-2h10a2 2 0 012 2v2H5V8zM4 10h16v9a1 1 0 01-1 1H5a1 1 0 01-1-1v-9zM12 6V4m0 2c-1.5 0-3-1-3-2.5S10.5 2 12 4c1.5-2 3-1.5 3 0S13.5 6 12 6zM12 10v10",
        "wallet" => "M3 10h18M7 15h.01M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z",
        "shield" => "M12 3l7 4v5c0 4.5-3 8.5-7 9-4-.5-7-4.5-7-9V7l7-4z M9.5 12l1.8 1.8L15 10",
        "face-scan" => "M4 8V6a2 2 0 012-2h2m8 0h2a2 2 0 012 2v2m0 8v2a2 2 0 01-2 2h-2M8 20H6a2 2 0 01-2-2v-2M9 10h.01M15 10h.01M9.5 15a4 4 0 005 0",
    ];
    $d = $icons[$icon] ?? $icons["grid"];
@endphp

<a href="{{ route($route) }}"
   class="nav-link group flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13.5px] font-medium transition-all duration-150 {{ $active ? "active bg-white/10 text-white" : "text-stone-400 hover:bg-white/5 hover:text-stone-100" }}">
    <span class="flex h-9 w-9 items-center justify-center rounded-lg transition {{ $active ? "bg-amber-500/20 text-amber-400" : "text-stone-400 group-hover:text-amber-300" }}">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}"/>
        </svg>
    </span>
    <span>{{ $label }}</span>
    @if($active)
        <svg class="ml-auto h-4 w-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    @endif
</a>
