@props([
    // [['label' => 'จ. 6', 'tip' => 'จันทร์ 6 ต.ค.', 'values' => ['on_time' => 30, 'late' => 4]], …]
    'rows' => [],
    // ['on_time' => 'ตรงเวลา', 'late' => 'มาสาย'] — bottom of the stack first.
    'series' => [],
    'unit' => '',
    'caption' => '',
])
@php
    // Stacked columns drawn straight into SVG. Geometry follows the chart
    // spec in the design system: ≤24px columns, 2px surface gap between
    // stacked segments, a 4px rounded data-end, hairline recessive grid.
    $w = 640; $h = 220;
    $left = 34; $right = 6; $top = 10; $bottom = 26;
    $plotW = $w - $left - $right; $plotH = $h - $top - $bottom;

    $keys = array_keys($series);
    $max = max(1, ...array_map(fn ($r) => array_sum($r['values']), $rows ?: [['values' => [0]]]));

    // A clean ceiling: 1/2/5 × 10ⁿ steps, four of them.
    $raw = $max / 4;
    $mag = 10 ** floor(log10($raw));
    $step = collect([1, 2, 5, 10])->map(fn ($m) => $m * $mag)->first(fn ($s) => $s >= $raw);
    $step = max(1, $step);
    $ceil = $step * ceil($max / $step);

    $n = max(1, count($rows));
    $band = $plotW / $n;
    // The SVG scales up with its card, so the cap sits below 24 to stay ≤24px on a wide screen.
    $barW = min(20, $band * .6);
    $y = fn ($v) => $top + $plotH - ($v / $ceil) * $plotH;
    $labelEvery = $n > 10 ? 2 : 1;
@endphp
<figure {{ $attributes->class('viz') }}>
    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="viz__svg" role="img" aria-label="{{ $caption }}">
        @for($t = 0; $t <= $ceil; $t += $step)
            <line x1="{{ $left }}" x2="{{ $w - $right }}" y1="{{ $y($t) }}" y2="{{ $y($t) }}" class="viz__grid" />
            <text x="{{ $left - 8 }}" y="{{ $y($t) + 4 }}" class="viz__tick" text-anchor="end">{{ number_format($t) }}</text>
        @endfor

        @foreach($rows as $i => $row)
            @php
                $cx = $left + $band * $i + $band / 2;
                $x = $cx - $barW / 2;
                $acc = 0;
                $visible = array_values(array_filter($keys, fn ($k) => ($row['values'][$k] ?? 0) > 0));
                $topKey = end($visible);
                $tip = ($row['tip'] ?? $row['label']).'|'.collect($keys)->map(fn ($k) => $series[$k].' '.number_format($row['values'][$k] ?? 0).' '.$unit)->join('|');
            @endphp
            <g class="viz__col">
                @foreach($keys as $s => $key)
                    @php $v = $row['values'][$key] ?? 0; @endphp
                    @continue($v <= 0)
                    @php
                        $y1 = $y($acc + $v);
                        $y0 = $y($acc) - ($acc > 0 ? 2 : 0);   // the 2px surface gap
                        $hh = max(0, $y0 - $y1);
                        $acc += $v;
                        $r = $key === $topKey ? min(4, $hh, $barW / 2) : 0;
                    @endphp
                    @if($r > 0)
                        <path class="viz__mark viz__mark--{{ $s + 1 }}"
                              d="M{{ $x }},{{ $y0 }} V{{ $y1 + $r }} Q{{ $x }},{{ $y1 }} {{ $x + $r }},{{ $y1 }} H{{ $x + $barW - $r }} Q{{ $x + $barW }},{{ $y1 }} {{ $x + $barW }},{{ $y1 + $r }} V{{ $y0 }} Z" />
                    @else
                        <rect class="viz__mark viz__mark--{{ $s + 1 }}" x="{{ $x }}" y="{{ $y1 }}" width="{{ $barW }}" height="{{ $hh }}" />
                    @endif
                @endforeach
                @if($i % $labelEvery === ($n - 1) % $labelEvery)
                    <text x="{{ $cx }}" y="{{ $h - 8 }}" class="viz__tick" text-anchor="middle">{{ $row['label'] }}</text>
                @endif
                {{-- Hit target: the whole band, taller and wider than the mark. --}}
                <rect class="viz__hit" x="{{ $left + $band * $i }}" y="{{ $top }}" width="{{ $band }}" height="{{ $plotH }}"
                      data-tip="{{ $tip }}" tabindex="0" />
            </g>
        @endforeach
    </svg>

    @if(count($series) > 1)
        <figcaption class="viz__legend">
            @foreach($series as $key => $label)
                <span><i class="viz__key viz__mark--{{ $loop->iteration }}"></i>{{ $label }}</span>
            @endforeach
        </figcaption>
    @endif

    <details class="viz__table">
        <summary>ดูเป็นตาราง</summary>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>วันที่</th>@foreach($series as $label)<th class="num">{{ $label }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row['tip'] ?? $row['label'] }}</td>
                            @foreach($keys as $key)<td class="num">{{ number_format($row['values'][$key] ?? 0) }}</td>@endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</figure>
