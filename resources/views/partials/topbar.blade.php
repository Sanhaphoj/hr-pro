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

    <button type="button" class="topbar__search" data-cmdk-open aria-keyshortcuts="Control+K">
        <x-icon name="search" />
        <span>ค้นหา…</span>
        <kbd>Ctrl K</kbd>
    </button>

    {{-- Flips straight away; the choice is saved in the background. --}}
    <button type="button" class="topbar__icon" data-scheme-toggle data-url="{{ route('settings.appearance.scheme') }}"
            aria-label="สลับโหมดมืด/สว่าง" title="สลับโหมดมืด/สว่าง">
        <x-icon name="moon" class="scheme-icon scheme-icon--dark" />
        <x-icon name="sun" class="scheme-icon scheme-icon--light" />
    </button>

    <a href="{{ route('notifications.index') }}" class="topbar__icon" aria-label="การแจ้งเตือน" title="การแจ้งเตือน">
        <x-icon name="bell" />
        @if($unread > 0)<span class="dot"></span>@endif
    </a>

    <div class="dropdown" data-dropdown>
        <div class="usermenu" data-dropdown-trigger>
            <span class="avatar" style="background: {{ avatar_color($user->email) }}">{{ $user->initials }}</span>
            <span class="usermenu__text">
                <span class="usermenu__name">{{ $user->name }}</span>
                <span class="usermenu__role cell-sub">{{ $user->roles->pluck('name')->join(', ') ?: 'ผู้ใช้งาน' }}</span>
            </span>
        </div>
        <div class="dropdown__menu" data-dropdown-menu>
            <a href="{{ route('profile.edit') }}" class="nav__link" style="color:var(--c-text);">
                <x-icon name="user" /> <span>โปรไฟล์ของฉัน</span>
            </a>
            <a href="{{ route('notifications.index') }}" class="nav__link" style="color:var(--c-text);">
                <x-icon name="bell" /> <span>การแจ้งเตือน @if($unread > 0)<x-badge color="red">{{ $unread }}</x-badge>@endif</span>
            </a>
            {{-- Shown only when the browser offers installation. --}}
            <button type="button" class="nav__link" data-install hidden style="width:100%; background:none; border:0; cursor:pointer; color:var(--c-text);">
                <x-icon name="download" /> <span>ติดตั้งแอปบนเครื่อง</span>
            </button>
            <form method="POST" action="{{ route('logout') }}" style="border-top:1px solid var(--c-border); margin-top:4px; padding-top:4px;">
                @csrf
                <button type="submit" class="nav__link" style="width:100%; background:none; border:0; cursor:pointer; color:var(--c-danger);">
                    <x-icon name="logout" /> <span>ออกจากระบบ</span>
                </button>
            </form>
        </div>
    </div>
</header>
