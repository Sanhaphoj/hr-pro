@php $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.'); @endphp
<x-card title="วันลาของฉัน" :subtitle="'ปี '.(now()->year + 543)">
    <x-slot:actions>
        <a href="{{ route('leave-requests.create') }}" class="btn btn--ghost btn--sm">ยื่นใบลา</a>
    </x-slot:actions>
    @if($personal['balances']->isEmpty())
        <x-empty icon="calendar" title="ยังไม่มีสิทธิ์วันลา" message="ฝ่ายบุคคลจะกำหนดสิทธิ์วันลาให้เมื่อเริ่มปีการลา" />
    @else
        <ul class="meters">
            @foreach($personal['balances'] as $balance)
                @php
                    $entitled = (float) $balance->entitled_days;
                    $left = max(0, $balance->remaining_days);
                    $pending = (float) $balance->pending_days;
                @endphp
                <li class="meter">
                    <div class="meter__head">
                        <span class="cell-strong">{{ $balance->leaveType?->name }}</span>
                        <span class="cell-sub num">เหลือ <b class="meter__value">{{ $fmt($left) }}</b> / {{ $fmt($entitled) }} วัน</span>
                    </div>
                    {{-- Remaining is the fill; days awaiting approval are held back as a hatched step. --}}
                    <div class="meter__track" role="meter" aria-valuemin="0" aria-valuemax="{{ $entitled }}" aria-valuenow="{{ $left }}"
                         aria-label="{{ $balance->leaveType?->name }} คงเหลือ {{ $fmt($left) }} วัน">
                        <span class="meter__fill" style="width: {{ $entitled ? $left / $entitled * 100 : 0 }}%"></span>
                        @if($pending > 0)
                            <span class="meter__pending" style="width: {{ $entitled ? $pending / $entitled * 100 : 0 }}%" title="รออนุมัติ {{ $fmt($pending) }} วัน"></span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>
