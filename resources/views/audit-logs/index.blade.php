@extends('layouts.app')
@section('title', 'บันทึกการใช้งานระบบ')

@section('content')
    <x-page-header title="บันทึกการใช้งานระบบ" subtitle="ประวัติการเปลี่ยนแปลงและกิจกรรมทั้งหมดในระบบ" />

    <x-card :padding="false">
        <div style="padding:16px 20px;">
            <form method="GET" action="{{ route('audit-logs.index') }}" class="toolbar">
                <div class="search">
                    <x-icon name="search" width="18" height="18" />
                    <input type="text" name="q" value="{{ $search }}" placeholder="ค้นหารายละเอียดหรือการกระทำ..." class="input">
                </div>
                <select name="action" class="select" style="max-width:200px;">
                    <option value="">ทุกการกระทำ</option>
                    @foreach($actionOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string) $action === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-button type="submit" variant="secondary" icon="filter">กรอง</x-button>
                @if($search || $action)
                    <x-button variant="ghost" icon="x" :href="route('audit-logs.index')">ล้างตัวกรอง</x-button>
                @endif
            </form>
        </div>

        @if($logs->isEmpty())
            <x-empty icon="list" title="ไม่พบบันทึกการใช้งาน"
                     message="{{ $search || $action ? 'ไม่มีรายการที่ตรงกับเงื่อนไขการค้นหา' : 'เมื่อมีกิจกรรมในระบบ จะปรากฏที่นี่' }}" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>เวลา</th>
                            <th>ผู้ใช้งาน</th>
                            <th>การกระทำ</th>
                            <th>รายละเอียด</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            @php
                                $actionColors = [
                                    'created' => 'green',
                                    'updated' => 'blue',
                                    'deleted' => 'red',
                                    'approved' => 'green',
                                    'rejected' => 'red',
                                    'login' => 'gray',
                                    'logout' => 'gray',
                                ];
                                $actionColor = $actionColors[$log->action] ?? 'gray';
                                $actionLabel = $actionLabels[$log->action] ?? $log->action;
                            @endphp
                            <tr>
                                <td>
                                    <div class="cell-strong">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                                    <div class="cell-sub">{{ $log->created_at->diffForHumans() }}</div>
                                </td>
                                <td>
                                    @if($log->user)
                                        <div class="flex items-center gap-2">
                                            <span class="avatar" style="background: {{ avatar_color($log->user->email) }}">{{ $log->user->initials }}</span>
                                            <span class="cell-strong">{{ $log->user->name }}</span>
                                        </div>
                                    @else
                                        <span class="cell-sub">ระบบ</span>
                                    @endif
                                </td>
                                <td><x-badge :color="$actionColor" :dot="true">{{ $actionLabel }}</x-badge></td>
                                <td>{{ $log->description }}</td>
                                <td class="cell-sub">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:0 20px 16px;">{{ $logs->links() }}</div>
        @endif
    </x-card>
@endsection
