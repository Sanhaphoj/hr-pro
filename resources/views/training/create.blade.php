@extends('layouts.app')
@section('title', 'เพิ่มหลักสูตร')

@section('content')
    <x-page-header title="เพิ่มหลักสูตรฝึกอบรม" subtitle="สร้างหลักสูตรใหม่สำหรับพนักงาน">
        <x-slot:breadcrumb><a href="{{ route('training.index') }}">← กลับรายการหลักสูตร</a></x-slot:breadcrumb>
    </x-page-header>

    <div style="max-width:640px;">
        <x-card>
            <form method="POST" action="{{ route('training.store') }}">
                @csrf
                <x-input name="title" label="ชื่อหลักสูตร" :value="old('title')" :required="true" />
                <div class="form-grid" style="margin-top:14px;">
                    <x-input name="hours" type="number" label="จำนวนชั่วโมง" :value="old('hours', 0)" :required="true" />
                    <x-input name="scheduled_date" type="date" label="วันที่จัด" :value="old('scheduled_date')" />
                </div>
                <div style="margin-top:14px;"><x-textarea name="description" label="รายละเอียดหลักสูตร" :value="old('description')" rows="5" /></div>
                <div class="flex items-center gap-2" style="margin-top:18px;">
                    <x-button type="submit" icon="check">บันทึกหลักสูตร</x-button>
                    <a href="{{ route('training.index') }}" class="btn btn--ghost">ยกเลิก</a>
                </div>
            </form>
        </x-card>
    </div>
@endsection
