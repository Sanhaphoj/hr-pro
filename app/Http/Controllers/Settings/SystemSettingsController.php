<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Mail\NotificationMail;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use App\Support\SettingsCatalogue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The settings hub, and one form per settings group. Each group is guarded by
 * its own permission (SettingsCatalogue); a super-admin holds them all.
 */
class SystemSettingsController extends Controller
{
    /** A form value meaning "leave the stored setting as it is". */
    private const KEEP = '__keep__';

    private const MESSAGES = [
        'required' => 'กรุณากรอก:attribute',
        'required_if' => 'กรุณากรอก:attribute เมื่อส่งอีเมลผ่าน SMTP',
        'string' => ':attributeไม่ถูกต้อง',
        'integer' => ':attributeต้องเป็นจำนวนเต็ม',
        'numeric' => ':attributeต้องเป็นตัวเลข',
        'between' => ':attributeต้องอยู่ระหว่าง :min ถึง :max',
        'max' => ':attributeต้องไม่เกิน :max',
        'min' => 'กรุณาเลือก:attributeอย่างน้อย :min รายการ',
        'email' => 'รูปแบบ:attributeไม่ถูกต้อง',
        'date_format' => 'รูปแบบ:attributeไม่ถูกต้อง (ชั่วโมง:นาที)',
        'after' => ':attributeต้องหลังเวลาเข้างาน',
        'in' => ':attributeที่เลือกไม่ถูกต้อง',
        'regex' => 'รูปแบบ:attributeไม่ถูกต้อง',
        'boolean' => ':attributeไม่ถูกต้อง',
        'array' => ':attributeไม่ถูกต้อง',
        'image' => ':attributeต้องเป็นไฟล์รูปภาพ',
        'mimes' => ':attributeต้องเป็นไฟล์ :values',
        'logo.max' => 'โลโก้ต้องมีขนาดไม่เกิน 1 MB',
        'favicon.max' => 'ไอคอนต้องมีขนาดไม่เกิน 512 KB',
    ];

    public function __construct(private readonly SettingsService $settings) {}

    public function index(Request $request): View
    {
        $groups = $this->allowedGroups($request->user());

        return view('settings.index', [
            'groups' => $groups,
            'counts' => array_map(fn (string $key) => $this->settings->customisedCount($key), array_combine(array_keys($groups), array_keys($groups))),
        ]);
    }

    public function edit(Request $request, string $group): View
    {
        $definition = $this->authorizeGroup($request->user(), $group);

        $values = [];
        $customised = [];
        $hasSecret = [];

        foreach (SettingsCatalogue::groupFields($group) as $name => $field) {
            $key = $group.'.'.$name;
            $value = $this->settings->effective($key);
            $customised[$name] = $this->settings->isCustomised($key);

            // A secret is never sent back to the browser, only whether one is set.
            if ($field['secret'] ?? false) {
                $hasSecret[$name] = filled($value);
                $value = null;
            }

            $values[$name] = $value;
        }

        return view('settings.system.edit', [
            'groupKey' => $group,
            'group' => $definition,
            'groups' => $this->allowedGroups($request->user()),
            'values' => $values,
            'customised' => $customised,
            'hasSecret' => $hasSecret,
            'lastChange' => $this->settings->lastChange($group),
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        $definition = $this->authorizeGroup($request->user(), $group);
        $fields = SettingsCatalogue::groupFields($group);

        [$rules, $attributes] = $this->rules($fields);
        $request->validate($rules, self::MESSAGES, $attributes);

        $values = [];
        foreach ($fields as $name => $field) {
            $value = $this->valueFor($request, $group, $name, $field);
            if ($value !== self::KEEP) {
                $values[$name] = $value;
            }
        }

        $changes = $this->settings->save($group, $values, $request->user());

        if ($changes) {
            AuditLogger::log('updated', 'แก้ไขการตั้งค่า: '.$definition['label'], null, ['changes' => $this->describe($changes)]);
        }

        return redirect()
            ->route('settings.system.edit', $group)
            ->with('success', $changes ? 'บันทึกการตั้งค่าเรียบร้อยแล้ว' : 'ไม่มีการเปลี่ยนแปลง');
    }

    public function reset(Request $request, string $group): RedirectResponse
    {
        $definition = $this->authorizeGroup($request->user(), $group);

        foreach (SettingsCatalogue::groupFields($group) as $name => $field) {
            if ($field['type'] === 'image' && $this->settings->isCustomised($group.'.'.$name)) {
                $this->forgetUpload($this->settings->effective($group.'.'.$name));
            }
        }

        $removed = $this->settings->reset($group);

        if ($removed) {
            AuditLogger::log('updated', 'คืนค่าเริ่มต้นการตั้งค่า: '.$definition['label'], null, ['keys' => $removed]);
        }

        return redirect()
            ->route('settings.system.edit', $group)
            ->with('success', $removed ? 'คืนค่าเริ่มต้นเรียบร้อยแล้ว' : 'หมวดนี้ใช้ค่าเริ่มต้นอยู่แล้ว');
    }

    /**
     * Send a message with the saved mail settings to the person asking.
     */
    public function testMail(Request $request): RedirectResponse
    {
        $this->authorizeGroup($request->user(), 'notifications');
        $email = $request->user()->email;

        try {
            Mail::to($email)->send(new NotificationMail(
                'ทดสอบการส่งอีเมล',
                'อีเมลฉบับนี้ส่งจากหน้าตั้งค่า เพื่อยืนยันว่าระบบส่งอีเมลได้ถูกต้อง',
                route('settings.system.edit', 'notifications'),
            ));
        } catch (\Throwable $e) {
            return back()->with('error', 'ส่งอีเมลไม่สำเร็จ: '.Str::limit($e->getMessage(), 180));
        }

        return back()->with('success', config('mail.default') === 'log'
            ? 'บันทึกอีเมลทดสอบลงไฟล์ log แล้ว (โหมดไม่ส่งจริง)'
            : "ส่งอีเมลทดสอบไปที่ {$email} แล้ว");
    }

    // ------------------------------------------------------------------------

    private function authorizeGroup(User $user, string $group): array
    {
        $definition = SettingsCatalogue::group($group);

        abort_if($definition === null, 404);
        abort_unless(SettingsCatalogue::allows($user, $definition), 403, 'คุณไม่มีสิทธิ์ตั้งค่าหมวดนี้');

        return $definition;
    }

    /** @return array<string, array> */
    private function allowedGroups(User $user): array
    {
        return array_filter(
            SettingsCatalogue::groups(),
            fn (array $group) => SettingsCatalogue::allows($user, $group),
        );
    }

    /** @return array{0: array, 1: array} rules and attribute names */
    private function rules(array $fields): array
    {
        $rules = [];
        $attributes = [];

        foreach ($fields as $name => $field) {
            $attributes[$name] = $field['label'];
            $fieldRules = $field['rules'] ?? [];

            switch ($field['type']) {
                case 'toggle':
                    $fieldRules = ['nullable', 'boolean'];
                    break;
                case 'days':
                    $rules[$name.'.*'] = ['integer', 'between:1,7'];
                    $attributes[$name.'.*'] = $field['label'];
                    break;
                case 'multi':
                    $rules[$name.'.*'] = [Rule::in(array_map('strval', array_keys($field['options'])))];
                    $attributes[$name.'.*'] = $field['label'];
                    break;
                case 'select':
                    $fieldRules[] = Rule::in(array_map('strval', array_keys($field['options'])));
                    break;
                case 'password':
                    $rules[$name.'_clear'] = ['nullable', 'boolean'];
                    break;
                case 'image':
                    $rules[$name.'_remove'] = ['nullable', 'boolean'];
                    break;
            }

            $rules[$name] = $fieldRules;
        }

        return [$rules, $attributes];
    }

    private function valueFor(Request $request, string $group, string $name, array $field): mixed
    {
        return match ($field['type']) {
            'image' => $this->imageValue($request, $group, $name),
            'password' => match (true) {
                $request->boolean($name.'_clear') => null,
                $request->filled($name) => (string) $request->input($name),
                default => self::KEEP,
            },
            'toggle' => $request->exists($name) ? $request->boolean($name) : self::KEEP,
            'days' => collect((array) $request->input($name, []))->map(fn ($day) => (int) $day)->unique()->sort()->values()->all(),
            // Kept in the order the options are listed, not the order ticked.
            'multi' => array_values(array_intersect(
                array_map('strval', array_keys($field['options'])),
                array_map('strval', (array) $request->input($name, [])),
            )),
            default => $request->exists($name) ? $this->scalar($request->input($name), $field) : self::KEEP,
        };
    }

    private function scalar(mixed $raw, array $field): mixed
    {
        $raw = is_string($raw) ? trim($raw) : $raw;

        if ($raw === '' || $raw === null) {
            return null;
        }

        return match (true) {
            $field['type'] === 'number' => isset($field['step']) ? (float) $raw : (int) $raw,
            ($field['cast'] ?? null) === 'int' => (int) $raw,
            $field['type'] === 'color' => strtolower((string) $raw),
            default => (string) $raw,
        };
    }

    private function imageValue(Request $request, string $group, string $name): mixed
    {
        $current = $this->settings->effective($group.'.'.$name);

        if ($request->hasFile($name)) {
            $file = $request->file($name);
            $stored = $file->storeAs('', $name.'-'.Str::lower(Str::random(12)).'.'.($file->extension() ?: 'png'), 'branding');
            $this->forgetUpload($current);

            return 'uploads/branding/'.$stored;
        }

        if ($request->boolean($name.'_remove')) {
            $this->forgetUpload($current);

            return null;
        }

        return self::KEEP;
    }

    /** Delete a logo this screen uploaded. A path set in .env is left alone. */
    private function forgetUpload(mixed $path): void
    {
        if (is_string($path) && str_starts_with($path, 'uploads/branding/')) {
            Storage::disk('branding')->delete(substr($path, strlen('uploads/branding/')));
        }
    }

    /** Readable before → after pairs for the audit log. Secrets are not written out. */
    private function describe(array $changes): array
    {
        $fields = SettingsCatalogue::fields();
        $described = [];

        foreach ($changes as $key => [$before, $after]) {
            $field = $fields[$key];
            $described[$field['label']] = ($field['secret'] ?? false)
                ? 'เปลี่ยนค่า'
                : $this->display($before).' → '.$this->display($after);
        }

        return $described;
    }

    private function display(mixed $value): string
    {
        return match (true) {
            $value === null || $value === '' || $value === [] => '(ว่าง)',
            is_bool($value) => $value ? 'เปิด' : 'ปิด',
            is_array($value) => implode(', ', $value),
            default => Str::limit((string) $value, 80),
        };
    }
}
