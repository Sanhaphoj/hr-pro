@props(['label', 'value', 'icon' => 'chart', 'color' => 'blue', 'meta' => null])
<div class="stat">
    <div class="stat__icon stat__icon--{{ $color }}"><x-icon :name="$icon" /></div>
    <div>
        <div class="stat__label">{{ $label }}</div>
        <div class="stat__value">{{ $value }}</div>
        @if($meta)<div class="stat__meta">{{ $meta }}</div>@endif
    </div>
</div>
