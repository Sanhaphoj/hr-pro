@php $user = auth()->user(); @endphp
<aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
        @include('partials.brand-mark')
        <span>
            <b>{{ config('app.name') }}</b>
            <small>{{ config('hrpro.company_name') }}</small>
        </span>
    </div>

    <nav class="nav">
        <div class="nav__group">{{ __('nav.main') }}</div>
        <x-nav-link :href="route('dashboard')" pattern="dashboard" icon="dashboard">{{ __('nav.dashboard') }}</x-nav-link>
        <x-nav-link :href="route('onboarding.index')" pattern="onboarding.*" icon="check-circle">{{ __('nav.onboarding') }}</x-nav-link>

        @if($user->hasAnyPermission(['employees.view', 'departments.view', 'positions.view']) || $user->isSuperAdmin())
            <div class="nav__group">{{ __('nav.people') }}</div>
            @can('employees.view')
                <x-nav-link :href="route('employees.index')" pattern="employees.*" icon="users">{{ __('nav.employees') }}</x-nav-link>
            @endcan
            @can('departments.view')
                <x-nav-link :href="route('departments.index')" pattern="departments.*" icon="building">{{ __('nav.departments') }}</x-nav-link>
            @endcan
            @can('positions.view')
                <x-nav-link :href="route('positions.index')" pattern="positions.*" icon="briefcase">{{ __('nav.positions') }}</x-nav-link>
            @endcan
        @endif

        <div class="nav__group">{{ __('nav.leave_time') }}</div>
        <x-nav-link :href="route('leave-requests.index')" pattern="leave-requests.*" icon="calendar">{{ __('nav.my_leave') }}</x-nav-link>
        <x-nav-link :href="route('leave-calendar.index')" pattern="leave-calendar.*" icon="grid">{{ __('nav.leave_calendar') }}</x-nav-link>
        <x-nav-link :href="route('time-clock.show')" pattern="time-clock.*" icon="map-pin">{{ __('nav.time_clock') }}</x-nav-link>
        <x-nav-link :href="route('attendance.index')" pattern="attendance.*" icon="clock">{{ __('nav.attendance') }}</x-nav-link>
        @can('work-locations.view')
            <x-nav-link :href="route('work-locations.index')" pattern="work-locations.*" icon="target">{{ __('nav.work_locations') }}</x-nav-link>
        @endcan
        @can('leave-approvals.view')
            <x-nav-link :href="route('leave-approvals.index')" pattern="leave-approvals.*" icon="check-circle">{{ __('nav.leave_approvals') }}</x-nav-link>
        @endcan
        @can('leave-types.view')
            <x-nav-link :href="route('leave-types.index')" pattern="leave-types.*" icon="list">{{ __('nav.leave_types') }}</x-nav-link>
        @endcan

        @if($user->hasAnyPermission(['payroll.view', 'recruitment.view', 'performance.view', 'training.view']) || $user->isSuperAdmin())
            <div class="nav__group">{{ __('nav.hr_ops') }}</div>
            @can('payroll.view')
                <x-nav-link :href="route('payroll.index')" pattern="payroll.*" icon="wallet">{{ __('nav.payroll') }}</x-nav-link>
            @endcan
            @can('recruitment.view')
                <x-nav-link :href="route('recruitment.index')" pattern="recruitment.*" icon="user">{{ __('nav.recruitment') }}</x-nav-link>
            @endcan
            @can('performance.view')
                <x-nav-link :href="route('performance.index')" pattern="performance.*" icon="chart">{{ __('nav.performance') }}</x-nav-link>
            @endcan
            @can('training.view')
                <x-nav-link :href="route('training.index')" pattern="training.*" icon="briefcase">{{ __('nav.training') }}</x-nav-link>
            @endcan
        @endif

        <div class="nav__group">{{ __('nav.org') }}</div>
        <x-nav-link :href="route('org-chart.index')" pattern="org-chart.*" icon="sitemap">{{ __('nav.org_chart') }}</x-nav-link>
        @can('documents.view')
            <x-nav-link :href="route('documents.index')" pattern="documents.*" icon="inbox">{{ __('nav.documents') }}</x-nav-link>
        @endcan
        <x-nav-link :href="route('announcements.index')" pattern="announcements.*" icon="megaphone">{{ __('nav.announcements') }}</x-nav-link>
        @can('reports.view')
            <x-nav-link :href="route('reports.index')" pattern="reports.*" icon="chart">{{ __('nav.reports') }}</x-nav-link>
        @endcan

        @if($user->hasAnyPermission(['users.view', 'roles.view', 'audit-logs.view']) || $user->isSuperAdmin())
            <div class="nav__group">{{ __('nav.admin') }}</div>
            @can('users.view')
                <x-nav-link :href="route('settings.users.index')" pattern="settings.users.*" icon="user">{{ __('nav.users') }}</x-nav-link>
            @endcan
            @can('roles.view')
                <x-nav-link :href="route('settings.roles.index')" pattern="settings.roles.*" icon="shield">{{ __('nav.roles') }}</x-nav-link>
            @endcan
            @can('audit-logs.view')
                <x-nav-link :href="route('audit-logs.index')" pattern="audit-logs.*" icon="cog">{{ __('nav.audit_logs') }}</x-nav-link>
            @endcan
        @endif

        {{-- Open to every account: the hub shows each person what their roles let them change. --}}
        <x-nav-link :href="route('settings.index')" pattern="settings.index|settings.system.*|settings.appearance.*" icon="sliders">{{ __('nav.settings') }}</x-nav-link>

        <div class="nav__group">{{ __('nav.language') }}</div>
        <div class="nav__lang">
            <a href="{{ route('locale.update', 'th') }}" class="nav__lang-btn {{ app()->getLocale() === 'th' ? 'is-active' : '' }}">ไทย</a>
            <a href="{{ route('locale.update', 'en') }}" class="nav__lang-btn {{ app()->getLocale() === 'en' ? 'is-active' : '' }}">EN</a>
        </div>
    </nav>

    <button type="button" class="sidebar__collapse" data-toggle="rail" aria-label="ย่อ/ขยายเมนู">
        <x-icon name="chevron-left" />
        <span>ย่อเมนู</span>
    </button>
</aside>
<div class="scrim" id="scrim"></div>
