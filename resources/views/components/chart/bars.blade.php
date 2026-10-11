@props([
    // [['label' => 'ลาป่วย', 'value' => 12.5], …] — already in the order to show.
    'rows' => [],
    'unit' => '',
])
@php
    // One series, so no legend: the card title says what is plotted. The
    // value rides at the tip of each bar in text ink, never the bar colour.
    $max = max(1, ...array_map(fn ($r) => (float) $r['value'], $rows ?: [['value' => 0]]));
    $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
@endphp
<ul {{ $attributes->class('hbars') }}>
    @foreach($rows as $row)
        <li class="hbars__row" data-tip="{{ $row['label'] }}|{{ $fmt($row['value']) }} {{ $unit }}" tabindex="0">
            <span class="hbars__label">{{ $row['label'] }}</span>
            <span class="hbars__track">
                <span class="hbars__bar" style="width: {{ max(1.5, $row['value'] / $max * 100) }}%"></span>
                <span class="hbars__value">{{ $fmt($row['value']) }}</span>
            </span>
        </li>
    @endforeach
</ul>
