@php $user = auth()->user(); @endphp
<aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
        <span class="logo">HR</span>
        <span>
            <b>HR PRO</b>
            <small>{{ config('hrpro.company_name') }}</small>
        </span>
    </div>

    <nav class="nav">
        <div class="nav__group">หน้าหลัก</div>
        <x-nav-link :href="route('dashboard')" pattern="dashboard" icon="dashboard">แดชบอร์ด</x-nav-link>

        @if($user->hasAnyPermission(['employees.view', 'departments.view', 'positions.view']) || $user->isSuperAdmin())
            <div class="nav__group">บุคลากร</div>
            @can('employees.view')
                <x-nav-link :href="route('employees.index')" pattern="employees.*" icon="users">พนักงาน</x-nav-link>
            @endcan
            @can('departments.view')
                <x-nav-link :href="route('departments.index')" pattern="departments.*" icon="building">แผนก</x-nav-link>
            @endcan
            @can('positions.view')
                <x-nav-link :href="route('positions.index')" pattern="positions.*" icon="briefcase">ตำแหน่งงาน</x-nav-link>
            @endcan
        @endif

        <div class="nav__group">การลา &amp; เวลางาน</div>
        <x-nav-link :href="route('leave-requests.index')" pattern="leave-requests.*" icon="calendar">การลาของฉัน</x-nav-link>
        <x-nav-link :href="route('attendance.index')" pattern="attendance.*" icon="clock">ลงเวลาทำงาน</x-nav-link>
        @can('leave-approvals.view')
            <x-nav-link :href="route('leave-approvals.index')" pattern="leave-approvals.*" icon="check-circle">อนุมัติการลา</x-nav-link>
        @endcan
        @can('leave-types.view')
            <x-nav-link :href="route('leave-types.index')" pattern="leave-types.*" icon="list">ประเภทการลา</x-nav-link>
        @endcan

        <div class="nav__group">องค์กร</div>
        <x-nav-link :href="route('announcements.index')" pattern="announcements.*" icon="megaphone">ประกาศ</x-nav-link>
        @can('reports.view')
            <x-nav-link :href="route('reports.index')" pattern="reports.*" icon="chart">รายงาน</x-nav-link>
        @endcan

        @if($user->hasAnyPermission(['users.view', 'roles.view', 'audit-logs.view']) || $user->isSuperAdmin())
            <div class="nav__group">ผู้ดูแลระบบ</div>
            @can('users.view')
                <x-nav-link :href="route('settings.users.index')" pattern="settings.users.*" icon="user">ผู้ใช้งานระบบ</x-nav-link>
            @endcan
            @can('roles.view')
                <x-nav-link :href="route('settings.roles.index')" pattern="settings.roles.*" icon="shield">บทบาท &amp; สิทธิ์</x-nav-link>
            @endcan
            @can('audit-logs.view')
                <x-nav-link :href="route('audit-logs.index')" pattern="audit-logs.*" icon="cog">บันทึกการใช้งาน</x-nav-link>
            @endcan
        @endif
    </nav>
</aside>
<div class="scrim" id="scrim"></div>
