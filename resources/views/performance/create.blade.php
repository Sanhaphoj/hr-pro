@extends('layouts.app')
@section('title', 'เพิ่มการประเมิน')

@section('content')
    <x-page-header title="บันทึกผลการประเมิน" subtitle="ประเมินผลการปฏิบัติงานของพนักงาน">
        <x-slot:breadcrumb><a href="{{ route('performance.index') }}">← กลับรายการประเมินผล</a></x-slot:breadcrumb>
    </x-page-header>

    <div style="max-width:640px;">
        <x-card>
            <form method="POST" action="{{ route('performance.store') }}">
                @csrf
                <div class="form-grid">
                    <x-select name="employee_id" label="พนักงาน" :options="$employees" :selected="old('employee_id')" placeholder="— เลือกพนักงาน —" :required="true" />
                    <x-input name="period" label="งวดการประเมิน" :value="old('period', date('Y').'-H'.(date('n') <= 6 ? '1' : '2'))" :required="true" placeholder="เช่น 2026-H1" />
                </div>

                <div style="margin-top:14px;">
                    <x-select name="score" label="คะแนนรวม (1–5)" :required="true"
                        :options="['5' => '5 — ดีเยี่ยม', '4' => '4 — ดี', '3' => '3 — ปานกลาง', '2' => '2 — ต้องปรับปรุง', '1' => '1 — ต่ำกว่าเกณฑ์']"
                        :selected="old('score', '3')" />
                </div>

                <div style="margin-top:14px;"><x-textarea name="strengths" label="จุดแข็ง" :value="old('strengths')" rows="3" /></div>
                <div style="margin-top:14px;"><x-textarea name="improvements" label="สิ่งที่ควรพัฒนา" :value="old('improvements')" rows="3" /></div>

                <div class="flex items-center gap-2" style="margin-top:18px;">
                    <x-button type="submit" icon="check">บันทึกการประเมิน</x-button>
                    <a href="{{ route('performance.index') }}" class="btn btn--ghost">ยกเลิก</a>
                </div>
            </form>
        </x-card>
    </div>
@endsection
