@extends('layouts.app')
@section('title', 'แก้ไขตำแหน่ง')

@section('content')
    <x-page-header title="แก้ไขตำแหน่ง" subtitle="{{ $position->title }}">
        <x-slot:actions>
            <x-button href="{{ route('positions.index') }}" variant="ghost" icon="arrow-left">ย้อนกลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card title="ข้อมูลตำแหน่ง">
        <form method="POST" action="{{ route('positions.update', $position) }}">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <x-input name="title" label="ชื่อตำแหน่ง" :value="$position->title" :required="true" />
                <x-input name="code" label="รหัสตำแหน่ง" :value="$position->code" :required="true" hint="เช่น DEV-01, HR-MGR" />
                <x-select name="department_id" label="แผนก" :options="$departments" :selected="$position->department_id" placeholder="ไม่ระบุ" />
                <x-select name="level" label="ระดับ" :options="$levels" :selected="$position->level" :required="true" placeholder="เลือกระดับ" />
                <div class="col-span-2">
                    <x-textarea name="description" label="รายละเอียด" :value="$position->description" />
                </div>
                <div class="col-span-2">
                    <x-checkbox name="is_active" label="เปิดใช้งาน" :checked="$position->is_active" />
                </div>
            </div>
            <div class="flex items-center gap-1" style="margin-top:18px;">
                <x-button type="submit" icon="check">บันทึกการเปลี่ยนแปลง</x-button>
                <x-button href="{{ route('positions.index') }}" variant="ghost">ยกเลิก</x-button>
            </div>
        </form>
    </x-card>
@endsection
