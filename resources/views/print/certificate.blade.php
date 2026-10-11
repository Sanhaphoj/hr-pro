@extends('layouts.print')

@php
    $current = $employee->status !== 'terminated';
    $until = $employee->termination_date ?? now();
    $title = $withSalary ? 'หนังสือรับรองการทำงานและเงินเดือน' : 'หนังสือรับรองการทำงาน';
    // "เพื่อ" is already part of the sentence, so a purpose typed with it is not doubled.
    $purposeText = $purpose ? preg_replace('/^เพื่อ\s*/u', '', $purpose) : null;
    $company = config('hrpro.company.name');
@endphp

@section('title', $title.' · '.$employee->full_name)
@section('filename', ($withSalary ? 'หนังสือรับรองเงินเดือน_' : 'หนังสือรับรองการทำงาน_').$employee->employee_code)
@section('back', route('employees.show', $employee))

@section('options')
    <form method="GET" action="{{ route('employees.certificate', $employee) }}" class="doc-options">
        <label class="doc-check">
            <input type="checkbox" name="salary" value="1" @checked($withSalary) onchange="this.form.submit()">
            ระบุเงินเดือน
        </label>
        <input type="text" name="number" value="{{ $number }}" class="doc-field" placeholder="เลขที่หนังสือ" maxlength="40" aria-label="เลขที่หนังสือ">
        <input type="text" name="purpose" value="{{ $purpose }}" class="doc-field doc-field--wide" placeholder="วัตถุประสงค์ เช่น ประกอบการขอสินเชื่อ" maxlength="150" aria-label="วัตถุประสงค์">
        <button type="submit" class="doc-btn">ปรับเอกสาร</button>
    </form>
@endsection

@section('content')
    <article class="sheet">
        <x-doc.letterhead center />

        <div class="letter-meta">
            <div>ที่ {{ $number ?: '....................................' }}</div>
            <div class="letter-meta__right">วันที่ {{ thai_date(now()) }}</div>
        </div>

        <h1 class="doc-title">{{ $title }}</h1>

        <div class="letter" style="margin-top: 6mm;">
            <p>
                หนังสือฉบับนี้ให้ไว้เพื่อรับรองว่า <b>{{ $employee->full_name }}</b>
                @if($employee->national_id)
                    เลขประจำตัวประชาชน {{ format_national_id($employee->national_id) }}
                @endif
                {{ $current ? 'เป็นพนักงานของ' : 'เคยเป็นพนักงานของ' }} {{ $company }}
                ตำแหน่ง {{ $employee->position?->title ?? '—' }}
                สังกัดแผนก {{ $employee->department?->name ?? '—' }}
                ประเภทการจ้าง {{ $typeLabel }}
                @if($employee->hire_date)
                    @if($current)
                        โดยเริ่มปฏิบัติงานตั้งแต่วันที่ {{ thai_date($employee->hire_date) }} จนถึงปัจจุบัน
                    @else
                        ตั้งแต่วันที่ {{ thai_date($employee->hire_date) }} ถึงวันที่ {{ thai_date($until) }}
                    @endif
                    รวมระยะเวลา {{ thai_duration($employee->hire_date, $until) }}
                @endif
                @if($withSalary && $employee->base_salary !== null)
                    {{ $current ? 'ปัจจุบันได้รับเงินเดือนเดือนละ' : 'ได้รับเงินเดือนครั้งสุดท้ายเดือนละ' }}
                    {{ number_format((float) $employee->base_salary, 2) }} บาท ({{ thai_baht_text($employee->base_salary) }})
                @endif
            </p>
            <p>
                @if($purposeText)
                    หนังสือรับรองฉบับนี้ออกให้ตามคำร้องขอของพนักงาน เพื่อ{{ $purposeText }}
                @else
                    หนังสือรับรองฉบับนี้ออกให้ตามคำร้องขอของพนักงาน เพื่อใช้เป็นหลักฐานประกอบการติดต่อตามที่พนักงานประสงค์
                @endif
                ทั้งนี้ ข้อความข้างต้นเป็นความจริงทุกประการ
            </p>
            <p>ให้ไว้ ณ วันที่ {{ thai_date(now()) }}</p>
        </div>

        <div class="letter-sign">
            <div class="stamp">ประทับตรา<br>บริษัท</div>
            <div>
                <div class="letter-sign__lead">ขอแสดงความนับถือ</div>
                <x-doc.signature :role="config('hrpro.company.signatory_title')" :name="config('hrpro.company.signatory_name')" :position="$company" :dated="false" />
            </div>
        </div>

        <x-doc.footer note="หนังสือรับรองนี้สมบูรณ์เมื่อมีลายมือชื่อผู้มีอำนาจลงนามและประทับตราบริษัท" />
    </article>
@endsection
