@props(['badgeKey', 'size' => 40])
@php
    $badge = \App\Support\Badges::get($badgeKey);
    $color = $badge['color'] ?? '#6b7280';
    $initials = $badge['initials'] ?? null;
    $uid = 'bi' . uniqid();
@endphp
{{-- Medal silhouette from Google Material Symbols "military_tech" (Apache 2.0) — star
     cutout dropped, initials (or the owl artwork for coruja_noturna) placed in its
     place on a white disc instead. --}}
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 -960 960 960" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <defs>
        <clipPath id="{{ $uid }}"><circle cx="480" cy="-700" r="138"/></clipPath>
    </defs>
    <path fill="{{ $color }}"
        d="M280-880h400v314q0 23-10 41t-28 29l-142 84 28 92h152l-124 88 48 152-124-94-124 94 48-152-124-88h152l28-92-142-84q-18-11-28-29t-10-41v-314Z"/>
    <circle cx="480" cy="-700" r="140" fill="#fff"/>
    {{-- Owl artwork temporarily disabled — showing generic initials for every badge
         until final reference icons are ready (see owl-badge-art.blade.php, unused
         for now but kept for when this is revisited). --}}
    <text x="480" y="-700" text-anchor="middle" dominant-baseline="central"
        font-size="130" font-weight="700" font-family="ui-sans-serif, system-ui, sans-serif"
        fill="{{ $color }}">{{ $initials }}</text>
</svg>
