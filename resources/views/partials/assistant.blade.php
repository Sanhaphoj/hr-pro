{{-- "ผู้ช่วย HR": a floating chat answered by Claude from the person's own
     records. Rendered only when an API key is configured. --}}
@if(\App\Services\AssistantService::enabled())
    <div class="aibot" data-assistant data-url="{{ route('assistant.ask') }}" data-user="{{ auth()->id() }}">
        <button type="button" class="aibot__fab" data-assistant-toggle aria-expanded="false" aria-controls="aibot-panel">
            <x-icon name="sparkles" />
            <span>ถามผู้ช่วย HR</span>
        </button>

        <section class="aibot__panel" id="aibot-panel" hidden aria-label="ผู้ช่วย HR">
            <header class="aibot__head">
                <span class="aibot__badge"><x-icon name="sparkles" /></span>
                <div>
                    <b>ผู้ช่วย HR</b>
                    <small>ตอบจากข้อมูลของคุณเท่านั้น · ขับเคลื่อนโดย Claude</small>
                </div>
                <button type="button" class="aibot__icon" data-assistant-clear title="เริ่มบทสนทนาใหม่" aria-label="เริ่มบทสนทนาใหม่"><x-icon name="rotate-ccw" width="16" height="16" /></button>
                <button type="button" class="aibot__icon" data-assistant-toggle title="ปิด" aria-label="ปิด"><x-icon name="x" width="16" height="16" /></button>
            </header>

            <div class="aibot__log" data-assistant-log aria-live="polite">
                <div class="aibot__hello">
                    <p>สวัสดีครับ คุณ{{ auth()->user()->employee?->first_name ?? auth()->user()->name }} 👋<br>ถามเรื่องวันลา การลงเวลา หรือสลิปเงินเดือนของคุณได้เลย</p>
                    <div class="aibot__chips">
                        @foreach(['ฉันเหลือวันลาพักร้อนกี่วัน?', 'เดือนนี้ฉันมาสายกี่ครั้ง?', 'สลิปเดือนล่าสุดหักภาษีไปเท่าไร?', 'ลาป่วยกี่วันต้องมีใบรับรองแพทย์?'] as $q)
                            <button type="button" class="aibot__chip" data-assistant-ask>{{ $q }}</button>
                        @endforeach
                    </div>
                </div>
            </div>

            <form class="aibot__form" data-assistant-form>
                <textarea rows="1" maxlength="1000" placeholder="พิมพ์คำถาม…" aria-label="คำถามถึงผู้ช่วย HR" required></textarea>
                <button type="submit" class="aibot__send" aria-label="ส่ง"><x-icon name="send" width="17" height="17" /></button>
            </form>
            <p class="aibot__note">ผู้ช่วยอาจผิดพลาดได้ ตรวจสอบตัวเลขสำคัญกับหน้าระบบหรือฝ่ายบุคคลอีกครั้ง</p>
        </section>
    </div>
@endif
