@php
    $user = auth()->user();
    $unread = $user->unreadNotificationsCount();
@endphp
<header class="topbar">
    <button class="sidebar__toggle" data-toggle="sidebar" aria-label="เมนู">
        <x-icon name="menu" />
    </button>
    <div class="topbar__title">@yield('title', 'แดชบอร์ด')</div>
    <div class="topbar__spacer"></div>

    <a href="{{ route('notifications.index') }}" class="topbar__icon" aria-label="การแจ้งเตือน" title="การแจ้งเตือน">
        <x-icon name="bell" />
        @if($unread > 0)<span class="dot"></span>@endif
    </a>

    <div data-dropdown style="position: relative;">
        <div class="usermenu" data-dropdown-trigger>
            <span class="avatar" style="background: {{ avatar_color($user->email) }}">{{ $user->initials }}</span>
            <span style="line-height: 1.2;">
                <span style="font-weight: 600; display: block;">{{ $user->name }}</span>
                <span class="cell-sub">{{ $user->roles->pluck('name')->join(', ') ?: 'ผู้ใช้งาน' }}</span>
            </span>
        </div>
        <div data-dropdown-menu style="display:none; position:absolute; right:0; top:52px; background:#fff; border:1px solid var(--c-border); border-radius:10px; box-shadow:var(--shadow-md); min-width:200px; padding:6px; z-index:50;">
            <a href="{{ route('profile.edit') }}" class="nav__link" style="color:var(--c-text);">
                <x-icon name="user" /> <span>โปรไฟล์ของฉัน</span>
            </a>
            <a href="{{ route('notifications.index') }}" class="nav__link" style="color:var(--c-text);">
                <x-icon name="bell" /> <span>การแจ้งเตือน @if($unread > 0)<x-badge color="red">{{ $unread }}</x-badge>@endif</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" style="border-top:1px solid var(--c-border); margin-top:4px; padding-top:4px;">
                @csrf
                <button type="submit" class="nav__link" style="width:100%; background:none; border:0; cursor:pointer; color:var(--c-danger);">
                    <x-icon name="logout" /> <span>ออกจากระบบ</span>
                </button>
            </form>
        </div>
    </div>
</header>
