@extends('layouts.app')
@section('title', 'เพิ่มแผนก')

@section('content')
    <x-page-header title="เพิ่มแผนก" subtitle="สร้างแผนกใหม่ในโครงสร้างองค์กร">
        <x-slot:actions>
            <x-button href="{{ route('departments.index') }}" variant="ghost" icon="arrow-left">ย้อนกลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card title="ข้อมูลแผนก">
        <form method="POST" action="{{ route('departments.store') }}">
            @csrf
            <div class="form-grid">
                <x-input name="name" label="ชื่อแผนก" :required="true" />
                <x-input name="code" label="รหัสแผนก" :required="true" hint="เช่น HR, IT, FIN" />
                <x-select name="parent_id" label="แผนกแม่" :options="$parents" placeholder="ไม่มี" />
                <x-select name="manager_id" label="หัวหน้าแผนก" :options="$managers" placeholder="ไม่ระบุ" />
                <div class="col-span-2">
                    <x-textarea name="description" label="รายละเอียด" />
                </div>
                <div class="col-span-2">
                    <x-checkbox name="is_active" label="เปิดใช้งาน" :checked="true" />
                </div>
            </div>
            <div class="flex items-center gap-1" style="margin-top:18px;">
                <x-button type="submit" icon="check">บันทึก</x-button>
                <x-button href="{{ route('departments.index') }}" variant="ghost">ยกเลิก</x-button>
            </div>
        </form>
    </x-card>
@endsection
