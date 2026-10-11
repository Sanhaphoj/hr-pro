@php
    // Every destination the sidebar would show this person, plus a few
    // actions. Gated exactly like the sidebar, so the palette never offers
    // a page that would answer 403. Keywords carry the English and the
    // everyday words people actually type.
    $user = auth()->user();
    $page = fn ($title, $route, $icon, $keywords = '', $perm = null) => (! $perm || $user->hasPermission($perm))
        ? ['group' => 'ไปที่หน้า', 'title' => $title, 'url' => route($route), 'icon' => $icon, 'keywords' => $keywords]
        : null;

    $items = array_values(array_filter([
        ['group' => 'ทำทันที', 'title' => 'ลงเวลาเข้า–ออกงาน', 'url' => route('time-clock.show'), 'icon' => 'map-pin', 'keywords' => 'clock in out punch เข้างาน ออกงาน ตอกบัตร'],
        ['group' => 'ทำทันที', 'title' => 'ยื่นใบลา', 'url' => route('leave-requests.create'), 'icon' => 'plus', 'keywords' => 'leave request ลาป่วย ลากิจ ลาพักร้อน'],
        $user->hasPermission('employees.create') ? ['group' => 'ทำทันที', 'title' => 'เพิ่มพนักงานใหม่', 'url' => route('employees.create'), 'icon' => 'plus', 'keywords' => 'new employee add hire'] : null,
        $user->hasPermission('announcements.manage') ? ['group' => 'ทำทันที', 'title' => 'เขียนประกาศ', 'url' => route('announcements.create'), 'icon' => 'megaphone', 'keywords' => 'announcement news post'] : null,
        \App\Services\AssistantService::enabled() ? ['group' => 'ทำทันที', 'title' => 'ถามผู้ช่วย HR', 'action' => 'assistant', 'icon' => 'sparkles', 'keywords' => 'ai assistant chat claude ถาม ช่วย'] : null,
        ['group' => 'ทำทันที', 'title' => 'สลับโหมดมืด / สว่าง', 'action' => 'scheme', 'icon' => 'moon', 'keywords' => 'dark light theme mode ธีม กลางคืน'],
        app()->getLocale() === 'th'
            ? ['group' => 'ทำทันที', 'title' => 'Switch to English', 'url' => route('locale.update', 'en'), 'icon' => 'refresh', 'keywords' => 'language ภาษา english']
            : ['group' => 'ทำทันที', 'title' => 'เปลี่ยนเป็นภาษาไทย', 'url' => route('locale.update', 'th'), 'icon' => 'refresh', 'keywords' => 'language ภาษา thai'],

        $page('แดชบอร์ด', 'dashboard', 'dashboard', 'dashboard home หน้าแรก'),
        $page('การลาของฉัน', 'leave-requests.index', 'calendar', 'my leave balance วันลา'),
        $page('ปฏิทินวันลา', 'leave-calendar.index', 'grid', 'calendar who is away ใครลา'),
        $page('ประวัติการลงเวลา', 'attendance.index', 'clock', 'attendance history timesheet'),
        $user->employee ? $page('สลิปเงินเดือนของฉัน', 'my-payslips.index', 'wallet', 'my payslip salary สลิป เงินเดือน ภาษี') : null,
        $page('อนุมัติการลา', 'leave-approvals.index', 'check-circle', 'approve approvals', 'leave-approvals.view'),
        $page('พนักงาน', 'employees.index', 'users', 'employees staff people', 'employees.view'),
        $page('แผนก', 'departments.index', 'building', 'departments', 'departments.view'),
        $page('ตำแหน่งงาน', 'positions.index', 'briefcase', 'positions jobs', 'positions.view'),
        $page('ผังองค์กร', 'org-chart.index', 'sitemap', 'org chart organisation'),
        $page('เงินเดือน', 'payroll.index', 'wallet', 'payroll salary payslip สลิป', 'payroll.view'),
        $page('สรรหาบุคลากร', 'recruitment.index', 'user', 'recruitment hiring candidates', 'recruitment.view'),
        $page('ประเมินผลงาน', 'performance.index', 'chart', 'performance review kpi', 'performance.view'),
        $page('ฝึกอบรม', 'training.index', 'award', 'training course', 'training.view'),
        $page('คลังเอกสาร', 'documents.index', 'inbox', 'documents files', 'documents.view'),
        $page('ประกาศ', 'announcements.index', 'megaphone', 'announcements news'),
        $page('รายงาน', 'reports.index', 'chart', 'reports statistics', 'reports.view'),
        $page('บริเวณลงเวลา', 'work-locations.index', 'target', 'work locations geofence gps', 'work-locations.view'),
        $page('ผู้ใช้งานระบบ', 'settings.users.index', 'user', 'users accounts', 'users.view'),
        $page('บทบาทและสิทธิ์', 'settings.roles.index', 'shield', 'roles permissions', 'roles.view'),
        $page('บันทึกการใช้งาน', 'audit-logs.index', 'cog', 'audit log', 'audit-logs.view'),
        $page('ตั้งค่า', 'settings.index', 'sliders', 'settings config'),
        $page('การแสดงผล', 'settings.appearance.edit', 'palette', 'appearance theme colour สีธีม'),
        $page('โปรไฟล์ของฉัน', 'profile.edit', 'user', 'profile password รหัสผ่าน'),
        $page('การแจ้งเตือน', 'notifications.index', 'bell', 'notifications'),
    ]));

    $icons = collect($items)->pluck('icon')->push('megaphone', 'search')->unique();
@endphp
<dialog class="cmdk" id="cmdk" aria-label="ค้นหาและคำสั่งด่วน"
        data-search-url="{{ route('search') }}" data-scheme-url="{{ route('settings.appearance.scheme') }}">
    <div class="cmdk__box">
        <label class="cmdk__field">
            <x-icon name="search" />
            <input type="text" class="cmdk__input" placeholder="ค้นหาหน้า คำสั่ง หรือชื่อพนักงาน…" autocomplete="off" spellcheck="false"
                   role="combobox" aria-expanded="true" aria-controls="cmdk-list" aria-autocomplete="list">
            <kbd>Esc</kbd>
        </label>
        <ul class="cmdk__list" id="cmdk-list" role="listbox"></ul>
        <div class="cmdk__foot">
            <span><kbd>↑</kbd><kbd>↓</kbd> เลือก</span>
            <span><kbd>Enter</kbd> เปิด</span>
            <span class="cmdk__foot-end"><kbd>Ctrl</kbd><kbd>K</kbd> เปิดได้จากทุกหน้า</span>
        </div>
    </div>
    <script type="application/json" id="cmdk-items">@json($items)</script>
    {{-- Icon sprites the script clones into rows, so results match the UI's own set. --}}
    <template id="cmdk-icons">
        @foreach($icons as $icon)<x-icon :name="$icon" data-icon="{{ $icon }}" />@endforeach
    </template>
</dialog>
