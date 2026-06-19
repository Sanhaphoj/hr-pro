@extends('layouts.app')
@section('title', 'เพิ่มพนักงาน')

@section('content')
    <x-page-header title="เพิ่มพนักงาน" subtitle="กรอกข้อมูลเพื่อสร้างประวัติพนักงานใหม่" />

    <x-card>
        <form method="POST" action="{{ route('employees.store') }}">
            @csrf

            <div class="form-grid">
                <x-input name="first_name" label="ชื่อ" :required="true" />
                <x-input name="last_name" label="นามสกุล" :required="true" />
                <x-input name="email" type="email" label="อีเมล" :required="true" />
                <x-input name="phone" label="เบอร์โทรศัพท์" />
                <x-input name="national_id" label="เลขบัตรประชาชน" />
                <x-input name="date_of_birth" type="date" label="วันเกิด" />
                <x-select name="gender" label="เพศ" :options="$genders" placeholder="— เลือกเพศ —" />
                <x-select name="department_id" label="แผนก" :options="$departments" placeholder="— เลือกแผนก —" />
                <x-select name="position_id" label="ตำแหน่ง" :options="$positions" placeholder="— เลือกตำแหน่ง —" />
                <x-select name="manager_id" label="หัวหน้างาน" :options="$managers" placeholder="— เลือกหัวหน้างาน —" />
                <x-select name="employment_type" label="ประเภทการจ้างงาน" :options="$employmentTypes" placeholder="— เลือกประเภท —" :required="true" />
                <x-select name="status" label="สถานะ" :options="$statuses" :selected="'active'" :required="true" />
                <x-input name="hire_date" type="date" label="วันเริ่มงาน" :required="true" />
                <x-input name="base_salary" type="number" label="เงินเดือน (บาท)" hint="ระบุเป็นตัวเลข" />
                <div class="col-span-2">
                    <x-textarea name="address" label="ที่อยู่" />
                </div>
                <x-input name="emergency_contact_name" label="ผู้ติดต่อฉุกเฉิน" />
                <x-input name="emergency_contact_phone" label="เบอร์ผู้ติดต่อฉุกเฉิน" />
            </div>

            <div class="flex items-center gap-2" style="margin-top:20px;">
                <x-button type="submit" icon="check">บันทึก</x-button>
                <x-button href="{{ route('employees.index') }}" variant="ghost">ยกเลิก</x-button>
            </div>
        </form>
    </x-card>
@endsection
