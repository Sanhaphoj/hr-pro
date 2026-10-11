<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Psr\Http\Client\ClientInterface;

/**
 * "ผู้ช่วย HR": answers an employee's questions about their own leave,
 * attendance, pay and the company's rules, using Claude.
 *
 * Each request carries only what the signed-in person may already see —
 * their own records and company-wide rules and announcements — never anyone
 * else's data, so the model cannot reveal what the person could not open
 * themselves. Nothing is written back; the assistant only explains and
 * points to the right screen.
 */
class AssistantService
{
    /** Turns of history kept per request (each turn = one question + answer). */
    public const MAX_TURNS = 10;

    /** An HTTP client to send through instead of the SDK's own (tests). */
    public function __construct(private readonly ?ClientInterface $transporter = null) {}

    public static function enabled(): bool
    {
        return (bool) config('hrpro.ai.enabled') && filled(config('hrpro.ai.api_key'));
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history  earlier turns, oldest first
     * @return array{reply: string, ok: bool}
     */
    public function reply(User $user, string $question, array $history = []): array
    {
        $messages = [];
        foreach (array_slice($history, -self::MAX_TURNS * 2) as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $question];

        try {
            $client = new Client(apiKey: config('hrpro.ai.api_key'));
            $response = $client->beta->messages->create(
                model: config('hrpro.ai.model', 'claude-opus-5-5'),
                maxTokens: 8000,
                // The rules come first and never change, so they are cached;
                // the person's own figures follow and differ per request.
                system: [
                    ['type' => 'text', 'text' => $this->rules(), 'cacheControl' => ['type' => 'ephemeral']],
                    ['type' => 'text', 'text' => $this->context($user)],
                ],
                messages: $messages,
                // Everyday HR questions: quick, short answers.
                outputConfig: ['effort' => 'low'],
                // If a safety classifier declines, retry on the model the API picks.
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
                requestOptions: ['timeout' => 60, 'maxRetries' => 2]
                    + ($this->transporter ? ['transporter' => $this->transporter] : []),
            );
        } catch (RateLimitException) {
            return ['reply' => 'ตอนนี้มีผู้ใช้งานผู้ช่วยจำนวนมาก กรุณาลองใหม่อีกครั้งในอีกสักครู่', 'ok' => false];
        } catch (APIStatusException $e) {
            Log::warning('HR assistant API error', ['status' => $e->getCode(), 'type' => $e->type?->value]);

            return ['reply' => 'ผู้ช่วยตอบไม่ได้ในขณะนี้ กรุณาลองใหม่ภายหลัง หรือติดต่อฝ่ายบุคคลโดยตรง', 'ok' => false];
        } catch (APIConnectionException) {
            return ['reply' => 'เชื่อมต่อผู้ช่วยไม่ได้ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองอีกครั้ง', 'ok' => false];
        }

        if ($response->stopReason === 'refusal') {
            return ['reply' => 'ขออภัย ผู้ช่วยตอบคำถามนี้ไม่ได้ ลองถามเรื่องการลา การลงเวลา หรือเงินเดือนของคุณดูนะครับ', 'ok' => false];
        }

        $text = '';
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return ['reply' => trim($text) ?: 'ขออภัย ผู้ช่วยไม่มีคำตอบสำหรับคำถามนี้', 'ok' => true];
    }

    /**
     * How the assistant behaves, and the company rules every employee may
     * read. Kept free of per-person or per-request values so it caches.
     */
    private function rules(): string
    {
        $work = config('hrpro.work');
        $days = collect($work['working_days'] ?? [1, 2, 3, 4, 5])->map(fn ($d) => thai_day($d % 7))->join(' ');
        $types = LeaveType::query()->orderBy('name')->get()
            ->map(fn (LeaveType $t) => "- {$t->name}: ".($t->days_per_year > 0 ? "{$t->days_per_year} วัน/ปี" : 'ไม่จำกัดวัน')
                .($t->is_paid ? ', ได้รับค่าจ้าง' : ', ไม่ได้รับค่าจ้าง')
                .($t->requires_approval ? ', ต้องรออนุมัติ' : ''))
            ->join("\n");

        return <<<TXT
        คุณคือ "ผู้ช่วย HR" ของ {$this->company()} ในระบบ HR PRO ตอบพนักงานเป็นภาษาไทยที่สุภาพ เป็นกันเอง กระชับ
        (ถ้าผู้ใช้ถามเป็นภาษาอังกฤษ ตอบเป็นภาษาอังกฤษ)

        สิ่งที่คุณทำได้: อธิบายข้อมูลของผู้ใช้เอง (วันลาคงเหลือ การลงเวลา สลิปเงินเดือน คำขอลา) กฎการลาและเวลาทำงานของบริษัท
        สรุปประกาศ และบอกว่าต้องไปทำที่หน้าไหนในระบบ
        สิ่งที่ทำไม่ได้: คุณไม่มีข้อมูลของพนักงานคนอื่น และไม่สามารถยื่นลา อนุมัติ หรือแก้ไขข้อมูลใด ๆ ได้
        ถ้าถูกถามเรื่องคนอื่นหรือขอให้ทำรายการ ให้บอกตรง ๆ และแนะนำหน้าที่เกี่ยวข้อง
        ตอบจากข้อมูลด้านล่างเท่านั้น ถ้าข้อมูลไม่พอ ให้บอกว่าไม่ทราบและแนะนำให้ติดต่อฝ่ายบุคคล อย่าเดาตัวเลข
        เรื่องภาษีและกฎหมายแรงงาน ให้ข้อมูลทั่วไปได้ แต่ย้ำว่าเป็นการประมาณการ ไม่ใช่คำแนะนำทางกฎหมายหรือภาษี
        ตอบสั้น ๆ ไม่เกิน 5–6 บรรทัด ใช้รายการหัวข้อย่อยเมื่อช่วยให้อ่านง่าย ไม่ต้องใช้หัวข้อใหญ่

        หน้าต่าง ๆ ในระบบ: ลงเวลาเข้า–ออก (/time-clock) · ยื่นใบลา (/leave-requests/create) · การลาของฉัน (/leave-requests)
        · ปฏิทินวันลา (/leave-calendar) · ประวัติการลงเวลา (/attendance) · สลิปเงินเดือนของฉัน (/my/payslips)
        · ประกาศ (/announcements) · โปรไฟล์และเปลี่ยนรหัสผ่าน (/profile)

        กฎของบริษัท
        - เวลาทำงาน {$work['start']}–{$work['end']} น. วันทำงาน: {$days}
        - เข้างานหลัง {$work['start']} เกิน {$work['late_grace_minutes']} นาที นับเป็นมาสาย
        - ประเภทการลา:
        {$types}
        TXT;
    }

    /**
     * The signed-in person's own records, as of now.
     */
    private function context(User $user): string
    {
        $now = Carbon::now();
        $lines = ['ข้อมูลของผู้ใช้ (ณ '.thai_date($now, 'long', true).')', '- ชื่อ: '.$user->name];
        $employee = $user->employee;

        if (! $employee) {
            $lines[] = '- บัญชีนี้ยังไม่ผูกกับทะเบียนพนักงาน จึงไม่มีข้อมูลการลา การลงเวลา หรือเงินเดือน';
        } else {
            $employee->loadMissing('department:id,name', 'position:id,title', 'manager:id,first_name,last_name');
            $lines[] = '- ตำแหน่ง: '.($employee->position?->title ?? 'ไม่ระบุ').' · แผนก: '.($employee->department?->name ?? 'ไม่ระบุ');
            $lines[] = '- หัวหน้า: '.($employee->manager?->full_name ?? 'ไม่ระบุ');
            $lines[] = '- เริ่มงาน: '.thai_date($employee->hire_date);

            $balances = LeaveBalance::where('employee_id', $employee->id)->where('year', $now->year)->with('leaveType:id,name')->get();
            $lines[] = '- วันลาปี '.($now->year + 543).': '.($balances->isEmpty() ? 'ยังไม่มีการกำหนดสิทธิ์'
                : $balances->map(fn ($b) => "{$b->leaveType?->name} ใช้ไป {$b->used_days} รออนุมัติ {$b->pending_days} คงเหลือ {$b->remaining_days} จาก {$b->entitled_days} วัน")->join('; '));

            $requests = LeaveRequest::where('employee_id', $employee->id)->with('leaveType:id,name')->latest('start_date')->limit(5)->get();
            if ($requests->isNotEmpty()) {
                $status = ['pending' => 'รออนุมัติ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ไม่อนุมัติ', 'cancelled' => 'ยกเลิก'];
                $lines[] = '- คำขอลาล่าสุด: '.$requests->map(fn ($r) => "{$r->leaveType?->name} ".thai_date($r->start_date, 'short').'–'.thai_date($r->end_date, 'short')
                    ." ({$r->total_days} วัน, ".($status[$r->status] ?? $r->status).')')->join('; ');
            }

            $month = Attendance::where('employee_id', $employee->id)
                ->whereDate('work_date', '>=', $now->copy()->startOfMonth())->get(['work_date', 'clock_in', 'clock_out', 'status', 'worked_minutes']);
            $today = $month->first(fn ($a) => $a->work_date->isToday());
            $lines[] = '- ลงเวลาเดือนนี้: '.$month->whereNotNull('clock_in')->count().' วัน มาสาย '.$month->where('status', 'late')->count()
                .' ครั้ง รวม '.round($month->sum('worked_minutes') / 60, 1).' ชั่วโมง';
            $lines[] = '- วันนี้: '.($today?->clock_in
                ? 'เข้างาน '.$today->clock_in->format('H:i').($today->clock_out ? ' ออกงาน '.$today->clock_out->format('H:i') : ' ยังไม่ลงเวลาออก')
                : 'ยังไม่ได้ลงเวลาเข้างาน');

            $slip = Payroll::where('employee_id', $employee->id)->where('status', 'finalized')
                ->orderByDesc('period_year')->orderByDesc('period_month')->first();
            if ($slip) {
                $lines[] = '- สลิปล่าสุด ('.thai_month($slip->period_month).' '.($slip->period_year + 543).'): รายได้ '.number_format($slip->earnings, 2)
                    .' หักรวม '.number_format((float) $slip->deductions, 2).' (ภาษี '.number_format((float) $slip->withholding_tax, 2)
                    .', ประกันสังคม '.number_format((float) $slip->social_security, 2).') สุทธิ '.number_format((float) $slip->net_pay, 2).' บาท';
            }
        }

        $news = Announcement::published()->visibleOrder()->limit(5)->get(['title', 'body', 'published_at']);
        if ($news->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'ประกาศล่าสุดของบริษัท';
            foreach ($news as $a) {
                $lines[] = '- '.thai_date($a->published_at, 'short').' '.$a->title.': '.mb_strimwidth(strip_tags((string) $a->body), 0, 400, '…');
            }
        }

        return implode("\n", $lines);
    }

    private function company(): string
    {
        return (string) config('hrpro.company.name', config('hrpro.company_name'));
    }
}
