@props(['color' => 'gray', 'dot' => false])
<span {{ $attributes->class(['badge', 'badge--'.$color, 'badge--dot' => $dot]) }}>{{ $slot }}</span>
