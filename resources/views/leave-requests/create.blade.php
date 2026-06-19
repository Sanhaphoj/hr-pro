@extends('layouts.app')
@section('title', 'ขอลางาน')

@section('content')
    <x-page-header title="ขอลางาน" subtitle="กรอกรายละเอียดเพื่อส่งคำขอลา">
        <x-slot:actions>
            <x-button :href="route('leave-requests.index')" variant="ghost" icon="arrow-left">กลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid--halves">
        <x-card title="แบบฟอร์มขอลา">
            <form method="POST" action="{{ route('leave-requests.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="col-span-2">
                        <x-select name="leave_type_id" label="ประเภทการลา" :options="$typeOptions"
                                  placeholder="— เลือกประเภทการลา —" :required="true" />
                    </div>
                    <x-input name="start_date" type="date" label="วันที่เริ่มลา" :required="true" />
                    <x-input name="end_date" type="date" label="วันที่สิ้นสุดการลา" :required="true" />
                    <div class="col-span-2">
                        <x-textarea name="reason" label="เหตุผลการลา" :required="true" :rows="4"
                                    hint="ไม่เกิน 500 ตัวอักษร" />
                    </div>
                    <div class="col-span-2">
                        <div class="field">
                            <label for="attachment">เอกสารแนบ (ถ้ามี)</label>
                            <input type="file" id="attachment" name="attachment"
                                   accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf"
                                   class="input @error('attachment') is-invalid @enderror">
                            <span class="field__hint">รองรับไฟล์ jpg, png หรือ pdf ขนาดไม่เกิน 4 MB</span>
                            @error('attachment')<span class="field__error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>
                <div style="margin-top:18px;">
                    <x-button type="submit" icon="check">ส่งคำขอลา</x-button>
                </div>
            </form>
        </x-card>

        <x-card title="วันลาคงเหลือ ปี {{ $year }}" subtitle="สิทธิ์การลาประจำปีปัจจุบันของคุณ">
            @if($balances->isEmpty())
                <x-empty icon="calendar" title="ยังไม่มีประเภทการลา"
                         message="กรุณาติดต่อฝ่ายบุคคลเพื่อตั้งค่าประเภทการลา" />
            @else
                <ul class="divide-list">
                    @foreach($balances as $row)
                        @php
                            $type = $row['type'];
                            $balance = $row['balance'];
                            $entitled = (float) $balance->entitled_days;
                            $used = (float) $balance->used_days;
                            $pending = (float) $balance->pending_days;
                            $remaining = (float) $balance->remaining_days;
                            $fmt = fn ($v) => rtrim(rtrim(number_format($v, 1), '0'), '.');
                            $pct = $entitled > 0 ? round($used / $entitled * 100) : 0;
                        @endphp
                        <li>
                            <div class="flex justify-between items-center" style="margin-bottom:6px;">
                                <span class="cell-strong">{{ $type->name }}</span>
                                <span class="cell-sub">คงเหลือ {{ $fmt($remaining) }} / {{ $fmt($entitled) }} วัน</span>
                            </div>
                            <div class="progress"><span style="width: {{ min(100, $pct) }}%"></span></div>
                            <div class="cell-sub" style="margin-top:4px;">
                                ใช้ไป {{ $fmt($used) }} วัน · รออนุมัติ {{ $fmt($pending) }} วัน
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
@endsection
