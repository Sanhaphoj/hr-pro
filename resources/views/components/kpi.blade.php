@props([
    'label',
    'value',
    'icon' => 'chart',
    'tone' => 'teal',   // teal|blue|green|amber|red
    'meta' => null,
    'href' => null,
])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->class(['kpi', 'kpi--'.$tone]) }}>
    <div class="kpi__top">
        <span class="kpi__label">{{ $label }}</span>
        <span class="kpi__icon"><x-icon :name="$icon" /></span>
    </div>
    <div class="kpi__value">{{ $value }}</div>
    @if($meta)<div class="kpi__meta">{{ $meta }}</div>@endif
</{{ $tag }}>
