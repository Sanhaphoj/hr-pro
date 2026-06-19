@props([
    'href' => null,
    'variant' => 'primary',   // primary|secondary|success|danger|ghost
    'type' => 'submit',
    'icon' => null,
    'size' => null,           // sm|null
])
@php
    $classes = array_filter(['btn', 'btn--'.$variant, $size ? 'btn--'.$size : null]);
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if($icon)<x-icon :name="$icon" />@endif{{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if($icon)<x-icon :name="$icon" />@endif{{ $slot }}
    </button>
@endif
