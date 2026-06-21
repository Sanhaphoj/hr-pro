@extends('layouts.app')
@section('title', 'ผังองค์กร')

@section('content')
    <x-page-header
        title="ผังองค์กร"
        subtitle="โครงสร้างองค์กรและสายบังคับบัญชา — แสดงจากข้อมูลแผนก หัวหน้าแผนก และจำนวนพนักงานปัจจุบัน" />

    <div class="stats">
        <x-stat label="แผนกทั้งหมด" :value="number_format($department_count)" icon="building" color="blue" meta="หน่วยงานในองค์กร" />
        <x-stat label="พนักงานทั้งหมด" :value="number_format($employee_count)" icon="users" color="green" meta="ทุกแผนกรวมกัน" />
        <x-stat label="ตำแหน่งงาน" :value="number_format($position_count)" icon="briefcase" color="amber" meta="ตำแหน่งที่กำหนดไว้" />
        <x-stat label="แผนกที่มีหัวหน้า" :value="number_format($managed_count)" icon="shield" color="blue"
                :meta="'จาก '.number_format($department_count).' แผนก'" />
    </div>

    <x-card>
        <x-slot:actions>
            @can('departments.view')
                <a href="{{ route('departments.index') }}" class="btn btn--ghost btn--sm">จัดการแผนก</a>
            @endcan
        </x-slot:actions>

        @if($roots->isEmpty())
            <x-empty icon="building" title="ยังไม่มีข้อมูลแผนก" message="เพิ่มแผนกเพื่อแสดงผังองค์กร" />
        @else
            <div class="org-tree">
                <ul>
                    <li>
                        <div class="org-node org-node--root">
                            <span class="org-node__logo">HR</span>
                            <div class="org-node__name">{{ $company }}</div>
                            <div class="org-node__meta">{{ number_format($department_count) }} แผนก · {{ number_format($employee_count) }} พนักงาน</div>
                        </div>
                        <ul>
                            @foreach($roots as $dept)
                                @include('org-chart._node', ['dept' => $dept])
                            @endforeach
                        </ul>
                    </li>
                </ul>
            </div>
        @endif
    </x-card>
@endsection
