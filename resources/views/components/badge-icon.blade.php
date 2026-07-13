@props(['badgeKey', 'size' => 40])
@php
    $badge = \App\Support\Badges::get($badgeKey);
    $color = $badge['color'] ?? '#6b7280';
    $emoji = $badge['emoji'] ?? '⭐';
@endphp
{{-- Placeholder icon: colored circle + emoji, until final reference icons are ready
     (the medal-silhouette + owl-artwork version this replaced is still in
     owl-badge-art.blade.php, unused for now, kept for when this is revisited). --}}
<div {{ $attributes->merge(['class' => 'rounded-full flex items-center justify-center flex-shrink-0']) }}
    style="width:{{ $size }}px;height:{{ $size }}px;background:{{ $color }}1a;font-size:{{ (int) ($size * 0.55) }}px;line-height:1;">
    {{ $emoji }}
</div>
