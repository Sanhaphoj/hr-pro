@extends('layouts.app')
@section('title', 'แก้ไขแผนก')

@section('content')
    <x-page-header title="แก้ไขแผนก" subtitle="{{ $department->name }}">
        <x-slot:actions>
            <x-button href="{{ route('departments.index') }}" variant="ghost" icon="arrow-left">ย้อนกลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card title="ข้อมูลแผนก">
        <form method="POST" action="{{ route('departments.update', $department) }}">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <x-input name="name" label="ชื่อแผนก" :value="$department->name" :required="true" />
                <x-input name="code" label="รหัสแผนก" :value="$department->code" :required="true" hint="เช่น HR, IT, FIN" />
                <x-select name="parent_id" label="แผนกแม่" :options="$parents" :selected="$department->parent_id" placeholder="ไม่มี" />
                <x-select name="manager_id" label="หัวหน้าแผนก" :options="$managers" :selected="$department->manager_id" placeholder="ไม่ระบุ" />
                <div class="col-span-2">
                    <x-textarea name="description" label="รายละเอียด" :value="$department->description" />
                </div>
                <div class="col-span-2">
                    <x-checkbox name="is_active" label="เปิดใช้งาน" :checked="$department->is_active" />
                </div>
            </div>
            <div class="flex items-center gap-1" style="margin-top:18px;">
                <x-button type="submit" icon="check">บันทึกการเปลี่ยนแปลง</x-button>
                <x-button href="{{ route('departments.index') }}" variant="ghost">ยกเลิก</x-button>
            </div>
        </form>
    </x-card>
@endsection
