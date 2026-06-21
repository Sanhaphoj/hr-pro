@extends('layouts.app')
@section('title', 'เปิดรับสมัคร')

@section('content')
    <x-page-header title="เปิดรับสมัครงาน" subtitle="สร้างประกาศรับสมัครตำแหน่งใหม่">
        <x-slot:breadcrumb><a href="{{ route('recruitment.index') }}">← กลับรายการสรรหา</a></x-slot:breadcrumb>
    </x-page-header>

    <div style="max-width:640px;">
        <x-card>
            <form method="POST" action="{{ route('recruitment.store') }}">
                @csrf
                <x-input name="title" label="ชื่อตำแหน่งที่รับสมัคร" :value="old('title')" :required="true" placeholder="เช่น นักพัฒนาซอฟต์แวร์" />

                <div class="form-grid" style="margin-top:14px;">
                    <x-select name="department_id" label="แผนก" :options="$departments" :selected="old('department_id')" placeholder="— เลือกแผนก —" />
                    <x-input name="openings" type="number" label="จำนวนอัตรา" :value="old('openings', 1)" :required="true" />
                </div>

                <div style="margin-top:14px;">
                    <x-select name="employment_type" label="ประเภทการจ้าง" :required="true"
                        :options="['full_time' => 'เต็มเวลา', 'part_time' => 'พาร์ทไทม์', 'contract' => 'สัญญาจ้าง', 'intern' => 'ฝึกงาน']"
                        :selected="old('employment_type', 'full_time')" />
                </div>

                <div style="margin-top:14px;">
                    <x-textarea name="description" label="รายละเอียดงาน" :value="old('description')" rows="5" />
                </div>

                <div class="flex items-center gap-2" style="margin-top:18px;">
                    <x-button type="submit" icon="check">สร้างประกาศ</x-button>
                    <a href="{{ route('recruitment.index') }}" class="btn btn--ghost">ยกเลิก</a>
                </div>
            </form>
        </x-card>
    </div>
@endsection
