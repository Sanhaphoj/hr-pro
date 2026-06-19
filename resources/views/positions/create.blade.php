@extends('layouts.app')
@section('title', 'เพิ่มตำแหน่ง')

@section('content')
    <x-page-header title="เพิ่มตำแหน่ง" subtitle="สร้างตำแหน่งงานใหม่">
        <x-slot:actions>
            <x-button href="{{ route('positions.index') }}" variant="ghost" icon="arrow-left">ย้อนกลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card title="ข้อมูลตำแหน่ง">
        <form method="POST" action="{{ route('positions.store') }}">
            @csrf
            <div class="form-grid">
                <x-input name="title" label="ชื่อตำแหน่ง" :required="true" />
                <x-input name="code" label="รหัสตำแหน่ง" :required="true" hint="เช่น DEV-01, HR-MGR" />
                <x-select name="department_id" label="แผนก" :options="$departments" placeholder="ไม่ระบุ" />
                <x-select name="level" label="ระดับ" :options="$levels" :required="true" placeholder="เลือกระดับ" />
                <div class="col-span-2">
                    <x-textarea name="description" label="รายละเอียด" />
                </div>
                <div class="col-span-2">
                    <x-checkbox name="is_active" label="เปิดใช้งาน" :checked="true" />
                </div>
            </div>
            <div class="flex items-center gap-1" style="margin-top:18px;">
                <x-button type="submit" icon="check">บันทึก</x-button>
                <x-button href="{{ route('positions.index') }}" variant="ghost">ยกเลิก</x-button>
            </div>
        </form>
    </x-card>
@endsection
