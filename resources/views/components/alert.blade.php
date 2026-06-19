@props(['type' => 'info', 'dismiss' => true])
@php
    $icon = ['success' => 'check-circle', 'error' => 'x-circle', 'warning' => 'alert', 'info' => 'info'][$type] ?? 'info';
@endphp
<div {{ $attributes->class(['alert', 'alert--'.$type]) }} @if($dismiss) data-auto-dismiss @endif role="alert">
    <x-icon :name="$icon" />
    <div>{{ $slot }}</div>
    @if($dismiss)<button type="button" class="alert__close" aria-label="ปิด">&times;</button>@endif
</div>
