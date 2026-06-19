@extends('layouts.app')
@section('title', 'แก้ไขพนักงาน')

@section('content')
    <x-page-header title="แก้ไขพนักงาน" :subtitle="$employee->full_name.' · '.$employee->employee_code">
        <x-slot:actions>
            <x-button href="{{ route('employees.show', $employee) }}" variant="ghost" icon="arrow-left">กลับ</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="POST" action="{{ route('employees.update', $employee) }}">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <x-input name="first_name" label="ชื่อ" :value="$employee->first_name" :required="true" />
                <x-input name="last_name" label="นามสกุล" :value="$employee->last_name" :required="true" />
                <x-input name="email" type="email" label="อีเมล" :value="$employee->email" :required="true" />
                <x-input name="phone" label="เบอร์โทรศัพท์" :value="$employee->phone" />
                <x-input name="national_id" label="เลขบัตรประชาชน" :value="$employee->national_id" />
                <x-input name="date_of_birth" type="date" label="วันเกิด" :value="optional($employee->date_of_birth)->format('Y-m-d')" />
                <x-select name="gender" label="เพศ" :options="$genders" :selected="$employee->gender" placeholder="— เลือกเพศ —" />
                <x-select name="department_id" label="แผนก" :options="$departments" :selected="$employee->department_id" placeholder="— เลือกแผนก —" />
                <x-select name="position_id" label="ตำแหน่ง" :options="$positions" :selected="$employee->position_id" placeholder="— เลือกตำแหน่ง —" />
                <x-select name="manager_id" label="หัวหน้างาน" :options="$managers" :selected="$employee->manager_id" placeholder="— เลือกหัวหน้างาน —" />
                <x-select name="employment_type" label="ประเภทการจ้างงาน" :options="$employmentTypes" :selected="$employee->employment_type" placeholder="— เลือกประเภท —" :required="true" />
                <x-select name="status" label="สถานะ" :options="$statuses" :selected="$employee->status" :required="true" />
                <x-input name="hire_date" type="date" label="วันเริ่มงาน" :value="optional($employee->hire_date)->format('Y-m-d')" :required="true" />
                <x-input name="base_salary" type="number" label="เงินเดือน (บาท)" :value="$employee->base_salary" hint="ระบุเป็นตัวเลข" />
                <div class="col-span-2">
                    <x-textarea name="address" label="ที่อยู่" :value="$employee->address" />
                </div>
                <x-input name="emergency_contact_name" label="ผู้ติดต่อฉุกเฉิน" :value="$employee->emergency_contact_name" />
                <x-input name="emergency_contact_phone" label="เบอร์ผู้ติดต่อฉุกเฉิน" :value="$employee->emergency_contact_phone" />
            </div>

            <div class="flex items-center gap-2" style="margin-top:20px;">
                <x-button type="submit" icon="check">บันทึกการแก้ไข</x-button>
                <x-button href="{{ route('employees.show', $employee) }}" variant="ghost">ยกเลิก</x-button>
            </div>
        </form>
    </x-card>
@endsection
