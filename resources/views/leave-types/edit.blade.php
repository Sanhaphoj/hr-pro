@extends('layouts.app')
@section('title', 'แก้ไขประเภทการลา')

@section('content')
    <x-page-header title="แก้ไขประเภทการลา" subtitle="{{ $leaveType->name }}">
        <x-slot:actions>
            <x-button href="{{ route('leave-types.index') }}" variant="ghost" icon="arrow-left">กลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card title="ข้อมูลประเภทการลา">
        <form method="POST" action="{{ route('leave-types.update', $leaveType) }}">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <x-input name="name" label="ชื่อประเภทการลา" :value="$leaveType->name" :required="true" hint="เช่น ลาพักร้อน, ลาป่วย" />
                <x-input name="code" label="รหัส" :value="$leaveType->code" :required="true" hint="เช่น ANNUAL, SICK" />
                <x-input name="days_per_year" type="number" min="0" max="366" label="วันลาต่อปี" :value="$leaveType->days_per_year" :required="true" hint="จำนวนวันลาที่ได้รับสิทธิ์ต่อปี" />
                <x-select name="color" label="สี" :options="$colors" :selected="$leaveType->color" :required="true" />

                <div class="col-span-2">
                    <x-textarea name="description" label="รายละเอียด" :value="$leaveType->description" hint="คำอธิบายเพิ่มเติม (ถ้ามี)" />
                </div>

                <x-checkbox name="requires_approval" label="ต้องได้รับการอนุมัติ" :checked="$leaveType->requires_approval" />
                <x-checkbox name="is_paid" label="เป็นการลาแบบมีค่าจ้าง" :checked="$leaveType->is_paid" />
                <x-checkbox name="is_active" label="เปิดใช้งาน" :checked="$leaveType->is_active" />
            </div>

            <div class="flex items-center gap-2" style="margin-top:20px;">
                <x-button type="submit" icon="check">บันทึกการเปลี่ยนแปลง</x-button>
                <x-button href="{{ route('leave-types.index') }}" variant="ghost">ยกเลิก</x-button>
            </div>
        </form>
    </x-card>
@endsection
