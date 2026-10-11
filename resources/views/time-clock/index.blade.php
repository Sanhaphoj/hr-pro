@extends('layouts.kiosk')
@section('title', 'ลงเวลา')

@section('content')
@php
    // Thai users read attendance dates in the Buddhist era, as the reference
    // app does; the rest of the console keeps the Gregorian year.
    $today_ = now();
    $thaiDate = $today_->translatedFormat('j F').' '.($today_->year + 543);

    $hasClockIn = $today && $today->clock_in;
    $hasClockOut = $today && $today->clock_out;

    $greeting = match (true) {
        $today_->hour < 12 => 'สวัสดีตอนเช้า',
        $today_->hour < 17 => 'สวัสดีตอนบ่าย',
        default => 'สวัสดีตอนเย็น',
    };

    // Where today stands, for the chip under the clock.
    [$dayState, $dayLabel] = match (true) {
        $hasClockOut => ['done', 'เลิกงานแล้ว'],
        (bool) $hasClockIn => ['working', 'กำลังทำงาน'],
        default => ['idle', 'รอลงเวลาเข้างาน'],
    };

    $duration = fn (int $minutes) => intdiv($minutes, 60).' ชม. '.($minutes % 60).' นาที';
    // A recorded punch is acknowledged with an animated check (below) rather
    // than a strip; only problems and notices use the alert strip.
    $hasFlash = session()->hasAny(['error', 'warning', 'info']) || $errors->any();
@endphp

<div class="tc">
    <div class="tc__phone">
        {{-- ---------------- Greeting bar ---------------- --}}
        <header class="tc__top">
            <span class="avatar" style="background: {{ avatar_color($user->email) }}">{{ $user->initials }}</span>
            <div class="tc__top-text">
                <small>{{ $greeting }}</small>
                <b>{{ $employee?->full_name ?? $user->name }}</b>
                <span>{{ $employee?->department?->name ? $employee->department->name.' · ' : '' }}{{ config('hrpro.company_name') }}</span>
            </div>
            <button type="button" class="tc__menu-btn" data-toggle="sidebar" aria-label="เมนู">
                <x-icon name="menu" />
            </button>
        </header>

        @if($hasFlash)
            <div class="tc__flash">
                @foreach(['error', 'warning', 'info'] as $type)
                    @if(session($type))
                        <x-alert :type="$type">{{ session($type) }}</x-alert>
                    @endif
                @endforeach
                @if($errors->any())
                    <x-alert type="error" :dismiss="false">{{ $errors->first() }}</x-alert>
                @endif
            </div>
        @endif

        @if(session('success'))
            {{-- Shown once after a punch is recorded; closes itself, or on a tap. --}}
            <div class="tc__done" id="tc-done" role="status" aria-live="polite">
                <div class="tc__done-card">
                    <svg class="tc__done-mark" viewBox="0 0 52 52" aria-hidden="true">
                        <circle class="tc__done-circle" cx="26" cy="26" r="24" />
                        <path class="tc__done-check" d="M15 27l7.5 7.5L38 19" />
                    </svg>
                    <b>{{ session('success') }}</b>
                    <span>{{ now()->format('H:i') }} น. · แตะเพื่อปิด</span>
                </div>
            </div>
        @endif

        @if(! $employee)
            <div class="tc__card tc__pad">
                <x-empty icon="user" title="ยังไม่มีโปรไฟล์พนักงาน"
                         message="บัญชีของคุณยังไม่ได้เชื่อมโยงกับข้อมูลพนักงาน จึงยังลงเวลาไม่ได้ กรุณาติดต่อฝ่ายบุคคล" />
            </div>
        @else
            <div class="tc__col tc__col--main">
                {{-- ---------------- The clock, on the coloured band ---------------- --}}
                <section class="tc__hero" aria-label="เวลาปัจจุบัน">
                    <div class="tc__day">วัน{{ thai_day($today_->dayOfWeek) }}ที่ {{ $thaiDate }}</div>
                    <div class="tc__time"><span id="tc-clock">{{ $today_->format('H:i') }}</span><small id="tc-sec">:{{ $today_->format('s') }}</small></div>
                    <div class="tc__chips">
                        <span class="tc__chip"><i class="tc__chip-dot tc__chip-dot--{{ $dayState }}"></i>{{ $dayLabel }}</span>
                        <span class="tc__chip"><x-icon name="clock" width="15" height="15" />เวลางาน {{ $workStart }}–{{ $workEnd }} น.</span>
                    </div>
                </section>

                {{-- ---------------- Location verdict + the two punch buttons ---------------- --}}
                <section class="tc__card tc__card--punch">
                    <div class="tc__geo tc__geo--pending" id="tc-geo">
                        <span class="tc__geo-icon"><x-icon name="satellite" width="18" height="18" /></span>
                        <div class="tc__geo-body">
                            <div class="tc__geo-head" id="tc-geo-head">กำลังอ่านตำแหน่ง…</div>
                            <div class="tc__geo-sub" id="tc-geo-sub">
                                @if($enforced)
                                    ระบบจะตรวจพิกัดของคุณกับบริเวณที่ผู้ดูแลระบบอนุญาต
                                @else
                                    ยังไม่ได้บังคับตรวจตำแหน่ง — ระบบบันทึกพิกัดไว้เป็นหลักฐานเท่านั้น
                                @endif
                            </div>
                        </div>
                        <span class="tc__pill" id="tc-pill">
                            <button type="button" id="tc-refresh" title="อ่านตำแหน่งใหม่" aria-label="อ่านตำแหน่งใหม่">
                                <x-icon name="refresh" width="17" height="17" />
                            </button>
                        </span>
                    </div>

                    <form method="POST" action="{{ route('time-clock.store') }}" id="tc-form">
                        @csrf
                        {{-- Filled in by the geolocation callback before either button unlocks. --}}
                        <input type="hidden" name="latitude" id="tc-lat">
                        <input type="hidden" name="longitude" id="tc-lng">
                        <input type="hidden" name="accuracy" id="tc-acc">
                        {{-- Mirrors the textarea in the confirmation dialog. --}}
                        <input type="hidden" name="reason" id="tc-reason">
                        <input type="hidden" name="off_site_reason" id="tc-offsite-input">
                        <input type="hidden" name="photo" id="tc-photo-input">
                        <input type="hidden" name="action" id="tc-action">

                        <div class="tc__punch">
                            <button type="submit" name="action" value="in" id="tc-in"
                                    class="tc__act tc__act--in {{ $hasClockIn ? 'tc__act--done' : '' }}"
                                    @disabled($hasClockIn)>
                                <span class="tc__dial"><x-icon :name="$hasClockIn ? 'check' : 'login'" /></span>
                                <span class="tc__act-label">เข้างาน</span>
                                <span class="tc__act-note">
                                    @if($hasClockIn)
                                        <b>{{ $today->clock_in->format('H:i') }} น.</b>
                                        {{ $today->clockInLocation?->name ?? ($today->clock_in_off_site ? 'นอกสถานที่' : 'ไม่ระบุบริเวณ') }}
                                    @else
                                        {{ $allowOffSite ? 'ลงเวลานอกบริเวณได้' : 'ต้องอยู่ในบริเวณที่กำหนด' }}
                                        @if($selfieIn)<br>ต้องถ่ายภาพยืนยัน @endif
                                    @endif
                                </span>
                            </button>

                            <button type="submit" name="action" value="out" id="tc-out"
                                    class="tc__act tc__act--out {{ $hasClockOut ? 'tc__act--done' : '' }}"
                                    @disabled(! $hasClockIn || $hasClockOut)>
                                <span class="tc__dial"><x-icon :name="$hasClockOut ? 'check' : 'logout'" /></span>
                                <span class="tc__act-label">ออกงาน</span>
                                <span class="tc__act-note">
                                    @if($hasClockOut)
                                        <b>{{ $today->clock_out->format('H:i') }} น.</b>
                                        {{ $today->clockOutLocation?->name ?? ($today->clock_out_off_site ? 'นอกสถานที่' : 'ไม่ระบุบริเวณ') }}
                                    @elseif(! $hasClockIn)
                                        ลงเวลาเข้างานก่อน
                                    @else
                                        {{ $allowOffSite ? 'ลงเวลานอกบริเวณได้' : 'ต้องอยู่ในบริเวณที่กำหนด' }}
                                        @if($selfieOut)<br>ต้องถ่ายภาพยืนยัน @endif
                                    @endif
                                </span>
                            </button>
                        </div>
                    </form>

                    @if($hasClockIn && ! $hasClockOut)
                        {{-- Live: the page script counts the minutes since clocking in. --}}
                        <div class="tc__shift" id="tc-shift"
                             data-start="{{ $today->clock_in->toIso8601String() }}"
                             data-end="{{ \Illuminate\Support\Carbon::parse($today->work_date->toDateString().' '.$workEnd)->toIso8601String() }}">
                            <div class="tc__shift-head"><span>ทำงานมาแล้ว</span><b id="tc-worked">{{ $duration((int) $today->clock_in->diffInMinutes(now())) }}</b></div>
                            <div class="tc__bar"><span id="tc-bar"></span></div>
                            <div class="tc__shift-foot"><span>เข้างาน {{ $today->clock_in->format('H:i') }} น.</span><span>เลิกงาน {{ $workEnd }} น.</span></div>
                        </div>
                    @elseif($hasClockOut)
                        <div class="tc__shift tc__shift--done">
                            <div class="tc__shift-head"><span>รวมเวลาทำงานวันนี้</span><b>{{ $duration((int) $today->worked_minutes) }}</b></div>
                            <div class="tc__bar"><span style="width:100%"></span></div>
                            <div class="tc__shift-foot"><span>เข้างาน {{ $today->clock_in->format('H:i') }} น.</span><span>ออกงาน {{ $today->clock_out->format('H:i') }} น.</span></div>
                        </div>
                    @endif
                </section>
            </div>{{-- /tc__col--main --}}

            <div class="tc__col">
                @if($summary)
                    {{-- ---------------- Team head-count, for supervisors ---------------- --}}
                    @php
                        $attendanceToday = route('attendance.index', ['date' => $today_->toDateString()]);
                        $tiles = [
                            ['wait', $summary['not_yet'], 'ยังไม่ลงเวลา', null],
                            ['ontime', $summary['on_time'], 'ทันเวลา', null],
                            ['late', $summary['late'], 'มาสาย', null],
                            ['outside', $summary['outside'], 'นอกบริเวณ', null],
                            ['leave', $summary['on_leave'], 'ลางาน', route('leave-approvals.index')],
                        ];
                    @endphp
                    <section class="tc__card">
                        <div class="tc__card-head">
                            <h2>ภาพรวมวันนี้</h2>
                            <a href="{{ $attendanceToday }}">ดูรายละเอียด</a>
                        </div>
                        <div class="tc__stats">
                            @foreach($tiles as [$tone, $count, $label, $link])
                                <a href="{{ $link ?? $attendanceToday }}" class="tc__stat tc__stat--{{ $tone }}">
                                    <i></i>
                                    <b>{{ number_format($count) }}</b>
                                    <span>{{ $label }}</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- ---------------- Today's entries ---------------- --}}
                <section class="tc__card">
                    <div class="tc__card-head">
                        <h2>รายการวันนี้</h2>
                        <a href="{{ route('attendance.index') }}">ประวัติทั้งหมด</a>
                    </div>
                    @if(! $hasClockIn)
                        <div class="tc__empty"><x-icon name="clock" /><span>ยังไม่มีการลงเวลาในวันนี้</span></div>
                    @else
                        <ol class="tc__log">
                            <li class="tc__log-item tc__log-item--in">
                                <span class="tc__log-dot"><x-icon name="login" width="16" height="16" /></span>
                                <div>
                                    <div class="tc__log-line"><b>เข้างาน</b><time>{{ $today->clock_in->format('H:i') }} น.</time></div>
                                    <div class="tc__log-meta">
                                        <span>{{ $today->clockInLocation?->name ?? ($today->clock_in_off_site ? 'นอกบริเวณ' : 'ไม่ระบุบริเวณ') }}</span>
                                        @if($today->status === 'late')<span class="tc__log-flag tc__log-flag--late">สาย</span>@endif
                                        @if($today->clock_in_off_site)<span class="tc__log-flag tc__log-flag--offsite">นอกสถานที่</span>@endif
                                        @if($today->clock_in_photo)
                                            <a class="tc__log-photo" href="{{ route('attendance.photo', [$today, 'in']) }}" target="_blank" title="ภาพยืนยันการเข้างาน">
                                                <img src="{{ route('attendance.photo', [$today, 'in']) }}" alt="ภาพยืนยันการเข้างาน" loading="lazy">
                                            </a>
                                        @endif
                                    </div>
                                    @if($today->status === 'late' && $today->late_reason)
                                        <span class="tc__log-reason">“{{ $today->late_reason }}”</span>
                                    @endif
                                    @if($today->clock_in_off_site && $today->clock_in_off_site_reason)
                                        <span class="tc__log-reason">“{{ $today->clock_in_off_site_reason }}”</span>
                                    @endif
                                </div>
                            </li>
                            @if($hasClockOut)
                                <li class="tc__log-item tc__log-item--out">
                                    <span class="tc__log-dot"><x-icon name="logout" width="16" height="16" /></span>
                                    <div>
                                        <div class="tc__log-line"><b>ออกงาน</b><time>{{ $today->clock_out->format('H:i') }} น.</time></div>
                                        <div class="tc__log-meta">
                                            <span>{{ $today->clockOutLocation?->name ?? ($today->clock_out_off_site ? 'นอกบริเวณ' : 'ไม่ระบุบริเวณ') }}</span>
                                            @if($today->left_early)<span class="tc__log-flag tc__log-flag--early">ออกก่อนเวลา</span>@endif
                                            @if($today->clock_out_off_site)<span class="tc__log-flag tc__log-flag--offsite">นอกสถานที่</span>@endif
                                            @if($today->clock_out_photo)
                                                <a class="tc__log-photo" href="{{ route('attendance.photo', [$today, 'out']) }}" target="_blank" title="ภาพยืนยันการออกงาน">
                                                    <img src="{{ route('attendance.photo', [$today, 'out']) }}" alt="ภาพยืนยันการออกงาน" loading="lazy">
                                                </a>
                                            @endif
                                        </div>
                                        @if($today->left_early && $today->early_leave_reason)
                                            <span class="tc__log-reason">“{{ $today->early_leave_reason }}”</span>
                                        @endif
                                        @if($today->clock_out_off_site && $today->clock_out_off_site_reason)
                                            <span class="tc__log-reason">“{{ $today->clock_out_off_site_reason }}”</span>
                                        @endif
                                    </div>
                                </li>
                            @endif
                        </ol>
                    @endif
                </section>

                {{-- ---------------- Approved zones with live distance ---------------- --}}
                <section class="tc__card">
                    <div class="tc__card-head">
                        <h2>บริเวณที่อนุญาต</h2>
                        <span class="tc__count">{{ $zones->count() }}</span>
                    </div>
                    @if($zones->isEmpty())
                        <div class="tc__empty"><x-icon name="map-pin" /><span>ผู้ดูแลระบบยังไม่ได้กำหนดบริเวณ — ขณะนี้ลงเวลาได้จากทุกที่</span></div>
                    @else
                        <ul class="tc__zones" id="tc-zones">
                            @foreach($zones as $zone)
                                <li class="tc__zone">
                                    <span class="tc__zone-icon"><x-icon name="map-pin" width="17" height="17" /></span>
                                    <div class="tc__zone-text">
                                        <div class="tc__zone-name">{{ $zone->name }}</div>
                                        <div class="tc__zone-addr">{{ $zone->address ?: 'รัศมี '.number_format($zone->radius_meters).' ม.' }}</div>
                                    </div>
                                    <span class="tc__zone-dist" data-distance>—</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>{{-- /tc__col --}}
        @endif
    </div>

    {{-- ----- Drawer: the only way back into the console from this screen ----- --}}
    {{-- Reuses the ids app.js already wires to [data-toggle="sidebar"]. --}}
    <aside class="tc__drawer" id="sidebar">
        <div class="tc__drawer-head">
            <span class="avatar" style="background: {{ avatar_color($user->email) }}">{{ $user->initials }}</span>
            <div>
                <b>{{ $user->name }}</b>
                <small>{{ $user->roles->pluck('name')->join(', ') ?: 'ผู้ใช้งาน' }}</small>
            </div>
        </div>

        <nav class="tc__drawer-nav">
            <a href="{{ route('dashboard') }}"><x-icon name="dashboard" /> <span>แดชบอร์ด</span></a>
            <a href="{{ route('attendance.index') }}"><x-icon name="history" /> <span>ประวัติการลงเวลา</span></a>
            <a href="{{ route('leave-requests.index') }}"><x-icon name="calendar" /> <span>การลาของฉัน</span></a>
            <a href="{{ route('profile.edit') }}"><x-icon name="user" /> <span>โปรไฟล์ของฉัน</span></a>

            @can('work-locations.view')
                <div class="tc__drawer-group">ผู้ดูแลระบบ</div>
                <a href="{{ route('work-locations.index') }}"><x-icon name="target" /> <span>บริเวณลงเวลา</span></a>
            @endcan

            <form method="POST" action="{{ route('logout') }}" class="tc__drawer-out">
                @csrf
                <button type="submit"><x-icon name="logout" /> <span>ออกจากระบบ</span></button>
            </form>
        </nav>
    </aside>
    <div class="scrim" id="scrim"></div>

    {{-- ----- Heads-up before a late arrival or an early exit is recorded ----- --}}
    <dialog class="tc__dialog" id="tc-dialog">
        <div class="tc__dialog-head">
            <span class="tc__dialog-icon" id="tc-dialog-icon"><x-icon name="alert" /></span>
            <div>
                <h3 id="tc-dialog-title">ลงเวลาสาย</h3>
                <p id="tc-dialog-text">คุณกำลังลงเวลาเข้างานหลังเวลาที่กำหนด</p>
            </div>
        </div>

        {{-- The photo already taken for this punch, with a way back to the camera. --}}
        <div class="tc__photo" id="tc-photo" hidden>
            <img id="tc-photo-thumb" alt="ภาพยืนยันที่ถ่ายไว้">
            <div class="tc__photo-text"><b>ถ่ายภาพยืนยันแล้ว</b>ภาพนี้จะแนบไปกับการลงเวลา</div>
            <x-button type="button" variant="secondary" size="sm" icon="camera" id="tc-photo-retake">ถ่ายใหม่</x-button>
        </div>

        {{-- Late / early: optional, the punch goes through either way. --}}
        <div id="tc-sched">
            <label class="tc__dialog-label" for="tc-dialog-reason">เหตุผลเรื่องเวลา <span id="tc-sched-need">(ไม่บังคับ)</span></label>
            <textarea class="input" id="tc-dialog-reason" rows="2" maxlength="250"
                      placeholder="เช่น รถติด, มีธุระด่วน — เว้นว่างไว้ก็ได้"></textarea>
            <div class="tc__dialog-count"><span id="tc-dialog-count">0</span>/250</div>
        </div>

        {{-- Off-site: optional, like the schedule reason. The punch is recorded
             as off-site whether or not a reason is given. --}}
        <div id="tc-offsite" hidden>
            <label class="tc__dialog-label" for="tc-offsite-reason">เหตุผลที่ลงเวลานอกสถานที่ <span>{{ $reasonRules['offSite'] ? '(จำเป็น)' : '(ไม่บังคับ)' }}</span></label>
            <textarea class="input" id="tc-offsite-reason" rows="2" maxlength="250"
                      placeholder="เช่น ไปพบลูกค้าที่บริษัท ABC{{ $reasonRules['offSite'] ? '' : ' — เว้นว่างไว้ก็ได้' }}"></textarea>
            <div class="tc__dialog-count"><span id="tc-offsite-count">0</span>/250</div>
        </div>

        <p class="tc__dialog-error" id="tc-dialog-error" role="alert" hidden></p>

        <div class="tc__dialog-actions">
            <x-button type="button" variant="ghost" id="tc-dialog-cancel">ยกเลิก</x-button>
            <x-button type="button" id="tc-dialog-confirm" icon="check">ยืนยันลงเวลา</x-button>
        </div>
    </dialog>

    {{-- ----- Full-screen front camera, for roles that require a photo ----- --}}
    <dialog class="cam" id="cam" data-state="live" aria-label="ถ่ายภาพยืนยันการลงเวลา">
        <div class="cam__view" id="cam-view">
            <video id="cam-video" class="cam__only-live" playsinline muted></video>
            <img id="cam-shot" class="cam__only-review" alt="ภาพที่ถ่าย">
            <div class="cam__guide cam__only-live"></div>
            <div class="cam__focus" id="cam-focus"></div>
            <div class="cam__flash" id="cam-flash"></div>
        </div>

        <div class="cam__error cam__only-error">
            <x-icon name="camera" />
            <div id="cam-error-text">เปิดกล้องไม่ได้</div>
            <x-button type="button" variant="secondary" id="cam-retry">ลองอีกครั้ง</x-button>
        </div>

        <div class="cam__top">
            <button type="button" class="cam__icon-btn" id="cam-close" aria-label="ปิดกล้อง"><x-icon name="x" /></button>
            <div class="cam__title">ถ่ายภาพยืนยัน<small id="cam-subtitle">ลงเวลาเข้างาน</small></div>
            <span></span>
        </div>

        <div class="cam__hint cam__only-live" id="cam-hint">แตะหน้าจอเพื่อโฟกัส</div>

        <div class="cam__bottom">
            <button type="button" class="cam__shutter cam__only-live" id="cam-shutter" aria-label="ถ่ายภาพ" disabled><span></span></button>
            <button type="button" class="cam__text-btn cam__text-btn--start cam__only-review" id="cam-retake">ถ่ายใหม่</button>
            <button type="button" class="cam__text-btn cam__text-btn--end cam__only-review" id="cam-use">ใช้ภาพนี้</button>
        </div>
    </dialog>

    {{-- ---------------- App-style bottom bar ---------------- --}}
    <nav class="tc__tabs">
        <a href="{{ route('leave-requests.index') }}" class="tc__tab">
            <span class="tc__tab-dot"><x-icon name="mail" width="18" height="18" /></span>
            <span>ลา</span>
        </a>
        <a href="{{ route('time-clock.show') }}" class="tc__tab tc__tab--active">
            <span class="tc__tab-dot"><x-icon name="clock" width="18" height="18" /></span>
            <span>ลงเวลา</span>
        </a>
        <a href="{{ route('attendance.index') }}" class="tc__tab">
            <span class="tc__tab-dot"><x-icon name="history" width="18" height="18" /></span>
            <span>ประวัติ</span>
        </a>
        <a href="{{ route('dashboard') }}" class="tc__tab">
            <span class="tc__tab-dot"><x-icon name="dashboard" width="18" height="18" /></span>
            <span>แดชบอร์ด</span>
        </a>
    </nav>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var clockEl = document.getElementById('tc-clock');
        var secEl = document.getElementById('tc-sec');
        var shiftEl = document.getElementById('tc-shift');
        var workedEl = document.getElementById('tc-worked');
        var barEl = document.getElementById('tc-bar');

        function pad(n) { return String(n).padStart(2, '0'); }

        if (clockEl) {
            var tick = function () {
                var now = new Date();
                clockEl.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes());
                if (secEl) {
                    secEl.textContent = ':' + pad(now.getSeconds());
                    // Restart the little tick on the seconds.
                    secEl.classList.remove('is-tick');
                    void secEl.offsetWidth;
                    secEl.classList.add('is-tick');
                }

                // Time worked so far, against the scheduled end of the day.
                if (shiftEl) {
                    var start = new Date(shiftEl.dataset.start);
                    var end = new Date(shiftEl.dataset.end);
                    var minutes = Math.max(0, Math.floor((now - start) / 60000));
                    workedEl.textContent = Math.floor(minutes / 60) + ' ชม. ' + (minutes % 60) + ' นาที';
                    var span = end - start;
                    var share = span > 0 ? (now - start) / span * 100 : 100;
                    barEl.style.width = Math.min(100, Math.max(0, share)) + '%';
                }
            };
            tick();
            setInterval(tick, 1000);
        }

        /* ----- Motion. Skipped for people who ask their system for less. ----- */
        var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // A recorded punch is acknowledged, then gets out of the way.
        var doneEl = document.getElementById('tc-done');
        if (doneEl) {
            var dismissDone = function () {
                if (!doneEl.isConnected || doneEl.classList.contains('is-leaving')) return;
                doneEl.classList.add('is-leaving');
                setTimeout(function () { doneEl.remove(); }, calm ? 0 : 320);
            };
            doneEl.addEventListener('click', dismissDone);
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') dismissDone();
            });
            setTimeout(dismissDone, 2600);
        }

        if (!calm) {
            // The head-count figures count up to their value.
            document.querySelectorAll('.tc__stat b').forEach(function (el) {
                var target = parseInt(el.textContent.replace(/,/g, ''), 10);
                if (!(target > 0)) return;
                el.textContent = '0';
                var started = null;
                function step(ts) {
                    if (started === null) started = ts;
                    var t = Math.min(1, (ts - started) / 800);
                    el.textContent = Math.round(target * (1 - Math.pow(1 - t, 3))).toLocaleString('en-US');
                    if (t < 1) requestAnimationFrame(step);
                }
                setTimeout(function () { requestAnimationFrame(step); }, 300);
            });

            // A ripple spreads from where a punch button is pressed.
            document.querySelectorAll('.tc__act').forEach(function (btn) {
                btn.addEventListener('pointerdown', function (e) {
                    if (btn.disabled) return;
                    var box = btn.getBoundingClientRect();
                    var size = Math.max(box.width, box.height) * 2;
                    var ripple = document.createElement('span');
                    ripple.className = 'tc__ripple';
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = (e.clientX - box.left - size / 2) + 'px';
                    ripple.style.top = (e.clientY - box.top - size / 2) + 'px';
                    btn.appendChild(ripple);
                    ripple.addEventListener('animationend', function () { ripple.remove(); });
                });
            });
        }

        var form = document.getElementById('tc-form');
        if (!form) return;

        var zones = @json($zonePayload);
        var enforced = @json($enforced);
        var maxAccuracy = @json($maxAccuracy);
        // Settings → Attendance: may a punch land off-site, and which reasons are compulsory.
        var allowOffSite = @json($allowOffSite);
        var reasonRules = @json($reasonRules);

        var pill = document.getElementById('tc-pill');
        var panel = document.getElementById('tc-geo');
        var headEl = document.getElementById('tc-geo-head');
        var subEl = document.getElementById('tc-geo-sub');
        var latInput = document.getElementById('tc-lat');
        var lngInput = document.getElementById('tc-lng');
        var accInput = document.getElementById('tc-acc');
        var buttons = [document.getElementById('tc-in'), document.getElementById('tc-out')];

        // A dial the server already closed (already clocked in, or not yet
        // clocked in) stays closed whatever the GPS reports.
        buttons.forEach(function (btn) {
            if (btn) { btn.dataset.serverLocked = btn.disabled ? '1' : ''; }
        });

        // Nearest zone while the last fix sits outside every zone (and the
        // fence is enforced); null otherwise. Read by the submit handler.
        var offSite = null;

        function setPanel(state, head, sub) {
            panel.className = 'tc__geo tc__geo--' + state;
            headEl.textContent = head;
            subEl.textContent = sub;
        }

        function allowPunch(allowed) {
            buttons.forEach(function (btn) {
                if (!btn) return;
                btn.disabled = btn.dataset.serverLocked === '1' || (enforced && !allowed);
                // The orange halo is the affordance: only an armed dial gets it.
                btn.classList.toggle('tc__act--ready', !btn.disabled);
            });
        }

        // The same haversine the server uses, so the distance on screen matches
        // the one the punch is judged by.
        function distance(lat1, lng1, lat2, lng2) {
            var toRad = Math.PI / 180;
            var dLat = (lat2 - lat1) * toRad;
            var dLng = (lng2 - lng1) * toRad;
            var a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
                + Math.cos(lat1 * toRad) * Math.cos(lat2 * toRad) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
            return 6371008.8 * 2 * Math.asin(Math.min(1, Math.sqrt(a)));
        }

        function format(meters) {
            return meters >= 1000
                ? (meters / 1000).toFixed(2) + ' กม.'
                : Math.round(meters) + ' ม.';
        }

        function onPosition(position) {
            pill.classList.remove('is-busy');

            var lat = position.coords.latitude;
            var lng = position.coords.longitude;
            var acc = Math.round(position.coords.accuracy);

            latInput.value = lat;
            lngInput.value = lng;
            accInput.value = acc;

            var rows = document.querySelectorAll('#tc-zones .tc__zone');
            var inside = null;
            var nearest = null;

            zones.forEach(function (zone, i) {
                var d = distance(lat, lng, zone.lat, zone.lng);
                var row = rows[i];
                if (row) {
                    row.querySelector('[data-distance]').textContent = format(d);
                    row.classList.toggle('tc__zone--inside', d <= zone.radius);
                }
                if (d <= zone.radius && (inside === null || d < inside.d)) { inside = { zone: zone, d: d }; }
                if (nearest === null || d < nearest.d) { nearest = { zone: zone, d: d }; }
            });

            var fix = lat.toFixed(6) + ', ' + lng.toFixed(6) + ' (±' + acc + ' ม.)';

            if (zones.length === 0) {
                setPanel('ok', 'พบตำแหน่งแล้ว', fix);
                allowPunch(true);
                return;
            }

            if (enforced && acc > maxAccuracy) {
                setPanel('warn', 'สัญญาณไม่แม่นยำพอ (เกิน ±' + maxAccuracy + ' ม.)',
                    'กรุณาออกไปในที่โล่งแล้วกดปุ่มรีเฟรชด้านบน · ' + fix);
                allowPunch(false);
                return;
            }

            if (inside) {
                offSite = null;
                setPanel('ok', 'อยู่ใน “' + inside.zone.name + '” · ห่าง ' + format(inside.d), fix);
                allowPunch(true);
                return;
            }

            var nearestText = nearest
                ? 'ใกล้ที่สุดคือ “' + nearest.zone.name + '” ห่าง ' + format(nearest.d) + ' (อนุญาตในรัศมี ' + nearest.zone.radius + ' ม.)'
                : fix;

            // Outside every zone: refused when the settings require punching
            // inside one, otherwise allowed and recorded as off-site.
            if (enforced && !allowOffSite) {
                offSite = null;
                setPanel('warn', 'อยู่นอกบริเวณที่อนุญาต — ต้องเข้าไปในบริเวณก่อนจึงจะลงเวลาได้', nearestText);
                allowPunch(false);
                return;
            }

            offSite = enforced ? nearest : null;
            setPanel('warn', 'อยู่นอกบริเวณที่อนุญาต — ลงเวลาได้ ระบบจะบันทึกว่านอกสถานที่', nearestText);
            allowPunch(true);
        }

        function onError(error) {
            offSite = null;
            pill.classList.remove('is-busy');
            latInput.value = '';
            lngInput.value = '';
            accInput.value = '';

            var messages = {
                1: ['ปฏิเสธการเข้าถึงตำแหน่ง', 'กรุณาอนุญาตตำแหน่งในการตั้งค่าเบราว์เซอร์แล้วกดรีเฟรช'],
                2: ['ระบุตำแหน่งไม่ได้', 'กรุณาตรวจสอบว่าเปิด GPS อยู่แล้วลองใหม่'],
                3: ['หมดเวลารอสัญญาณ', 'สัญญาณ GPS อ่อน กรุณากดรีเฟรชอีกครั้ง']
            };
            var m = messages[error.code] || ['อ่านตำแหน่งไม่สำเร็จ', 'กรุณาลองใหม่อีกครั้ง'];
            setPanel('error', m[0], m[1]);
            allowPunch(false);
        }

        function locate() {
            if (!navigator.geolocation) {
                setPanel('error', 'เบราว์เซอร์ไม่รองรับการระบุตำแหน่ง', 'กรุณาใช้เบราว์เซอร์รุ่นใหม่หรือแอปบนมือถือ');
                allowPunch(false);
                return;
            }

            // Browsers only expose geolocation on a secure origin (localhost
            // counts); on plain HTTP the call fails silently.
            if (!window.isSecureContext) {
                setPanel('error', 'ต้องเปิดผ่าน HTTPS', 'หน้านี้ต้องเข้าผ่าน https:// จึงจะระบุตำแหน่งได้');
                allowPunch(false);
                return;
            }

            pill.classList.add('is-busy');
            setPanel('pending', 'กำลังอ่านตำแหน่ง…', 'กรุณารอสักครู่');
            allowPunch(false);
            navigator.geolocation.getCurrentPosition(onPosition, onError, {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            });
        }

        /* ----- Heads-up before an off-schedule punch is recorded ------------ */
        var lateAfter = new Date(@json($lateAfter));
        var earlyBefore = new Date(@json($earlyBefore));

        var dialog = document.getElementById('tc-dialog');
        var dlgTitle = document.getElementById('tc-dialog-title');
        var dlgText = document.getElementById('tc-dialog-text');
        var dlgIcon = document.getElementById('tc-dialog-icon');
        var dlgReason = document.getElementById('tc-dialog-reason');
        var dlgCount = document.getElementById('tc-dialog-count');
        var actionInput = document.getElementById('tc-action');
        var reasonInput = document.getElementById('tc-reason');
        var offsiteInput = document.getElementById('tc-offsite-input');
        var schedBox = document.getElementById('tc-sched');
        var offsiteBox = document.getElementById('tc-offsite');
        var offsiteReason = document.getElementById('tc-offsite-reason');
        var offsiteCount = document.getElementById('tc-offsite-count');
        var confirmBtn = document.getElementById('tc-dialog-confirm');
        var schedNeed = document.getElementById('tc-sched-need');
        var dlgError = document.getElementById('tc-dialog-error');


        function minutesBetween(a, b) {
            return Math.max(1, Math.round(Math.abs(a - b) / 60000));
        }

        function describeGap(minutes) {
            var h = Math.floor(minutes / 60);
            var m = minutes % 60;
            return (h ? h + ' ชม. ' : '') + (m || !h ? m + ' นาที' : '').trim();
        }

        // Returns the warning to show, or null when the punch is on schedule.
        function offSchedule(action, now) {
            if (action === 'in' && now > lateAfter) {
                return {
                    tone: 'late',
                    title: 'ลงเวลาสาย',
                    text: 'คุณกำลังลงเวลาเข้างานช้ากว่ากำหนด ' + describeGap(minutesBetween(now, lateAfter))
                        + ' ระบบจะบันทึกสถานะเป็น “มาสาย”'
                };
            }
            if (action === 'out' && now < earlyBefore) {
                return {
                    tone: 'early',
                    title: 'ออกงานก่อนเวลา',
                    text: 'คุณกำลังลงเวลาออกงานก่อนกำหนด ' + describeGap(minutesBetween(earlyBefore, now))
                        + ' ระบบจะบันทึกว่าออกก่อนเวลา'
                };
            }
            return null;
        }

        function submitWith(action, reason, offSiteReasonText) {
            actionInput.value = action;
            reasonInput.value = reason || '';
            offsiteInput.value = offSiteReasonText || '';
            form.submit();
        }

        /* ----- Front-camera photo, for roles that require one ------------ */
        var selfieRequired = { 'in': @json($selfieIn), 'out': @json($selfieOut) };
        var photoInput = document.getElementById('tc-photo-input');
        var photoBox = document.getElementById('tc-photo');
        var photoThumb = document.getElementById('tc-photo-thumb');

        var cam = document.getElementById('cam');
        var camView = document.getElementById('cam-view');
        var camVideo = document.getElementById('cam-video');
        var camShot = document.getElementById('cam-shot');
        var camFocus = document.getElementById('cam-focus');
        var camFlash = document.getElementById('cam-flash');
        var camHint = document.getElementById('cam-hint');
        var camShutter = document.getElementById('cam-shutter');
        var camSubtitle = document.getElementById('cam-subtitle');
        var camErrorText = document.getElementById('cam-error-text');

        var stream = null;
        var shot = null;       // on the review screen, not yet accepted
        var captured = null;   // accepted for the punch being prepared
        var onAccept = null;   // what happens once a shot is accepted
        var pending = null;    // {action, warning, needsOffSite, needsPhoto}
        var streamTicket = 0;  // bumped per camera request; see startStream()

        function setCamState(state) {
            cam.dataset.state = state;
        }

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(function (track) { track.stop(); });
                stream = null;
            }
            camVideo.srcObject = null;
        }

        function camFail(message) {
            stopCamera();
            camErrorText.textContent = message;
            setCamState('error');
        }

        function startStream() {
            setCamState('live');
            camShutter.disabled = true;
            camHint.textContent = 'แตะหน้าจอเพื่อโฟกัส';

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.isSecureContext) {
                camFail('เบราว์เซอร์นี้เปิดกล้องไม่ได้ (ต้องเปิดผ่าน HTTPS)');
                return;
            }

            // Only the newest request may attach: a camera closed (or retried)
            // while the permission prompt was still up must not light up later.
            var ticket = ++streamTicket;

            navigator.mediaDevices.getUserMedia({
                // Phones rotate the frame to match a portrait screen on their own.
                video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false
            }).then(function (s) {
                if (ticket !== streamTicket || !cam.open) {
                    s.getTracks().forEach(function (track) { track.stop(); });
                    throw 'stale';
                }
                stream = s;
                camVideo.srcObject = s;
                return camVideo.play();
            }).then(function () {
                camShutter.disabled = false;
                camShutter.focus();
            }).catch(function (err) {
                if (err === 'stale' || ticket !== streamTicket) return;
                camFail('เปิดกล้องไม่ได้ — กรุณาอนุญาตการใช้กล้องในการตั้งค่าเบราว์เซอร์');
            });
        }

        // Opens the full-screen camera; `then` runs once a shot is accepted.
        function openCamera(action, then) {
            onAccept = then;
            shot = null;
            camSubtitle.textContent = action === 'in' ? 'ลงเวลาเข้างาน' : 'ลงเวลาออกงาน';
            cam.showModal();
            startStream();
        }

        // Where a tap lands in the video's own frame, 0–1 on each axis. The
        // picture is cropped to fill the screen (object-fit: cover) and
        // mirrored, so both are undone before asking the camera to focus.
        function tapToVideoPoint(clientX, clientY) {
            var box = camView.getBoundingClientRect();
            var vw = camVideo.videoWidth;
            var vh = camVideo.videoHeight;
            if (!vw || !vh) return null;

            var scale = Math.max(box.width / vw, box.height / vh);
            var dw = vw * scale;
            var dh = vh * scale;
            var ox = (box.width - dw) / 2;
            var oy = (box.height - dh) / 2;
            var x = box.width - (clientX - box.left);   // undo the mirror
            var y = clientY - box.top;

            return {
                x: Math.min(1, Math.max(0, (x - ox) / dw)),
                y: Math.min(1, Math.max(0, (y - oy) / dh))
            };
        }

        // Asks the camera to focus and meter on the point. Front cameras are
        // often fixed-focus and some browsers expose no controls at all, so
        // this is best-effort; the focus marker is shown regardless.
        function focusAt(point) {
            var track = stream && stream.getVideoTracks()[0];
            if (!track || !track.getCapabilities || !track.applyConstraints) return;

            var caps = track.getCapabilities();
            var supported = navigator.mediaDevices.getSupportedConstraints
                ? navigator.mediaDevices.getSupportedConstraints()
                : {};
            var wanted = {};

            if (supported.pointsOfInterest) {
                wanted.pointsOfInterest = [point];
            }
            if (caps.focusMode && caps.focusMode.indexOf('single-shot') !== -1) {
                wanted.focusMode = 'single-shot';
            } else if (caps.focusMode && caps.focusMode.indexOf('continuous') !== -1) {
                wanted.focusMode = 'continuous';
            }
            if (caps.exposureMode && caps.exposureMode.indexOf('continuous') !== -1) {
                wanted.exposureMode = 'continuous';
            }

            if (Object.keys(wanted).length) {
                track.applyConstraints({ advanced: [wanted] }).catch(function () {});
            }
        }

        camView.addEventListener('pointerdown', function (e) {
            if (cam.dataset.state !== 'live' || !stream) return;

            var box = camView.getBoundingClientRect();
            camFocus.style.left = (e.clientX - box.left) + 'px';
            camFocus.style.top = (e.clientY - box.top) + 'px';
            camFocus.classList.remove('is-on');
            void camFocus.offsetWidth;   // restart the animation on every tap
            camFocus.classList.add('is-on');
            camHint.textContent = 'โฟกัสแล้ว';

            var point = tapToVideoPoint(e.clientX, e.clientY);
            if (point) { focusAt(point); }
        });

        camShutter.addEventListener('click', function () {
            var vw = camVideo.videoWidth;
            var vh = camVideo.videoHeight;
            if (!vw || !vh) return;

            // Keep what the screen shows: the same crop object-fit applies,
            // capped at 800 px on the long side and never upscaled.
            var box = camView.getBoundingClientRect();
            var scale = Math.max(box.width / vw, box.height / vh);
            var sw = box.width / scale;
            var sh = box.height / scale;
            var sx = (vw - sw) / 2;
            var sy = (vh - sh) / 2;
            var out = Math.min(1, 800 / Math.max(sw, sh));

            var canvas = document.createElement('canvas');
            canvas.width = Math.round(sw * out);
            canvas.height = Math.round(sh * out);
            var ctx = canvas.getContext('2d');
            // Mirrored like the preview, so the review screen does not flip.
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(camVideo, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);

            shot = canvas.toDataURL('image/jpeg', 0.82);
            camShot.src = shot;
            camFlash.classList.remove('is-on');
            void camFlash.offsetWidth;
            camFlash.classList.add('is-on');
            stopCamera();
            setCamState('review');
        });

        document.getElementById('cam-retake').addEventListener('click', startStream);
        document.getElementById('cam-retry').addEventListener('click', startStream);

        document.getElementById('cam-use').addEventListener('click', function () {
            if (!shot) return;
            captured = shot;
            var then = onAccept;
            cam.close();
            if (then) { then(captured); }
        });

        document.getElementById('cam-close').addEventListener('click', function () {
            cam.close();
        });

        // However the camera closes — X, Esc, or accepting — the light goes off.
        cam.addEventListener('close', function () {
            stopCamera();
            onAccept = null;
        });

        // The reasons dialog, for a punch that is late, early or off-site.
        function openReasons() {
            var titles = [];
            var texts = [];
            if (pending.warning) {
                titles.push(pending.warning.title);
                texts.push(pending.warning.text);
            }
            if (pending.needsOffSite) {
                titles.push('นอกสถานที่');
                texts.push('คุณอยู่นอกบริเวณที่อนุญาต ห่างจาก “' + offSite.zone.name + '” ' + format(offSite.d)
                    + ' ระบบจะบันทึกว่าลงเวลานอกสถานที่');
            }
            dlgTitle.textContent = titles.join(' · ');
            dlgText.textContent = texts.join(' ');
            dlgIcon.className = 'tc__dialog-icon tc__dialog-icon--' + (pending.warning ? pending.warning.tone : 'offsite');

            schedBox.hidden = !pending.warning;
            offsiteBox.hidden = !pending.needsOffSite;
            photoBox.hidden = !pending.needsPhoto;
            if (pending.needsPhoto) { photoThumb.src = captured; }
            dlgReason.value = '';
            dlgCount.textContent = '0';
            offsiteReason.value = '';
            offsiteCount.textContent = '0';

            // Which explanation the settings make compulsory for this punch.
            var schedRequired = !!pending.warning && reasonRules[pending.warning.tone === 'late' ? 'late' : 'early'] === true;
            schedNeed.textContent = schedRequired ? '(จำเป็น)' : '(ไม่บังคับ)';
            dlgReason.placeholder = 'เช่น รถติด, มีธุระด่วน' + (schedRequired ? '' : ' — เว้นว่างไว้ก็ได้');
            dlgError.hidden = true;

            dialog.showModal();
            (pending.needsOffSite ? offsiteReason : dlgReason).focus();
        }

        function showDialogError(message, field) {
            dlgError.textContent = message;
            dlgError.hidden = false;
            field.focus();
        }

        function finish(reason, offSiteReasonText) {
            photoInput.value = pending.needsPhoto ? captured : '';
            submitWith(pending.action, reason, offSiteReasonText);
        }

        // Retaking from the reasons dialog: the camera stacks above it and an
        // accepted shot replaces the thumbnail. Closing it keeps the old one.
        document.getElementById('tc-photo-retake').addEventListener('click', function () {
            openCamera(pending.action, function (photo) { photoThumb.src = photo; });
        });

        form.addEventListener('submit', function (event) {
            // submitter is absent when we re-submit the form ourselves.
            var submitter = event.submitter;
            if (!submitter) return;

            var action = submitter.value;
            pending = {
                action: action,
                warning: offSchedule(action, new Date()),
                needsOffSite: offSite !== null,
                needsPhoto: selfieRequired[action] === true
            };

            if (!pending.warning && !pending.needsOffSite && !pending.needsPhoto) {
                actionInput.value = action;
                offsiteInput.value = '';
                photoInput.value = '';
                return;
            }

            event.preventDefault();

            // Without <dialog> the camera cannot open either.
            if (typeof dialog.showModal !== 'function') {
                if (pending.needsPhoto) {
                    window.alert('เบราว์เซอร์นี้ถ่ายภาพลงเวลาไม่ได้ กรุณาใช้เบราว์เซอร์รุ่นใหม่');
                    return;
                }
                submitWith(action, null, null);
                return;
            }

            captured = null;
            var askReasons = pending.warning || pending.needsOffSite;

            if (pending.needsPhoto) {
                // Camera first; then the reasons, or straight on to the punch.
                openCamera(action, function () {
                    if (askReasons) {
                        openReasons();
                    } else {
                        finish('', '');
                    }
                });
            } else {
                openReasons();
            }
        });

        offsiteReason.addEventListener('input', function () {
            offsiteCount.textContent = String(offsiteReason.value.length);
        });

        dlgReason.addEventListener('input', function () {
            dlgCount.textContent = String(dlgReason.value.length);
        });

        document.getElementById('tc-dialog-cancel').addEventListener('click', function () {
            dialog.close();
        });

        confirmBtn.addEventListener('click', function () {
            // A required photo is taken before this dialog ever opens.
            if (pending.needsPhoto && !captured) return;

            // The server refuses these too; checking here saves a round trip.
            var schedRequired = !schedBox.hidden && reasonRules[pending.warning.tone === 'late' ? 'late' : 'early'] === true;
            if (schedRequired && dlgReason.value.trim() === '') {
                showDialogError('กรุณาระบุเหตุผลก่อนลงเวลา', dlgReason);
                return;
            }
            if (!offsiteBox.hidden && reasonRules.offSite === true && offsiteReason.value.trim() === '') {
                showDialogError('กรุณาระบุเหตุผลที่ลงเวลานอกสถานที่', offsiteReason);
                return;
            }

            dialog.close();
            finish(
                schedBox.hidden ? '' : dlgReason.value,
                offsiteBox.hidden ? '' : offsiteReason.value
            );
        });

        document.getElementById('tc-refresh').addEventListener('click', locate);
        locate();

        // Refresh while the page stays open so a stale fix is never submitted.
        setInterval(locate, 60000);
    })();
</script>
@endpush
