@extends('layouts.app')
@section('title', 'เพิ่มประเภทการลา')

@section('content')
    <x-page-header title="เพิ่มประเภทการลา" subtitle="กำหนดหมวดหมู่การลาและสิทธิ์วันลาต่อปี">
        <x-slot:actions>
            <x-button href="{{ route('leave-types.index') }}" variant="ghost" icon="arrow-left">กลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card title="ข้อมูลประเภทการลา">
        <form method="POST" action="{{ route('leave-types.store') }}">
            @csrf
            <div class="form-grid">
                <x-input name="name" label="ชื่อประเภทการลา" :required="true" hint="เช่น ลาพักร้อน, ลาป่วย" />
                <x-input name="code" label="รหัส" :required="true" hint="เช่น ANNUAL, SICK" />
                <x-input name="days_per_year" type="number" min="0" max="366" label="วันลาต่อปี" :value="0" :required="true" hint="จำนวนวันลาที่ได้รับสิทธิ์ต่อปี" />
                <x-select name="color" label="สี" :options="$colors" :selected="'blue'" :required="true" />

                <div class="col-span-2">
                    <x-textarea name="description" label="รายละเอียด" hint="คำอธิบายเพิ่มเติม (ถ้ามี)" />
                </div>

                <x-checkbox name="requires_approval" label="ต้องได้รับการอนุมัติ" :checked="true" />
                <x-checkbox name="is_paid" label="เป็นการลาแบบมีค่าจ้าง" :checked="true" />
                <x-checkbox name="is_active" label="เปิดใช้งาน" :checked="true" />
            </div>

            <div class="flex items-center gap-2" style="margin-top:20px;">
                <x-button type="submit" icon="check">บันทึก</x-button>
                <x-button href="{{ route('leave-types.index') }}" variant="ghost">ยกเลิก</x-button>
            </div>
        </form>
    </x-card>
@endsection
