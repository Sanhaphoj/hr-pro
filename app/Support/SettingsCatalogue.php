<?php

namespace App\Support;

/**
 * Every setting an administrator can change on the settings screen.
 *
 * Each field names the config path(s) it overrides. SettingsService lays the
 * stored values over config() at boot, so code that already reads config —
 * the attendance service, the payroll run, the mailer — follows a change
 * without knowing settings exist. A group is the unit of access: one
 * permission opens one group, and a super-admin holds every permission.
 *
 * Field types: text, email, textarea, number, time, toggle, select, days,
 * multi, color, image, password.
 */
final class SettingsCatalogue
{
    private static ?array $groups = null;

    /**
     * @return array<string, array{label: string, description: string, icon: string, permission: string, sections: array<int, array>}>
     */
    public static function groups(): array
    {
        return self::$groups ??= [
            'company' => [
                'label' => 'ข้อมูลบริษัท',
                'description' => 'ชื่อ ที่อยู่ โลโก้ และผู้ลงนามบนเอกสารทางการ',
                'icon' => 'building',
                'permission' => 'settings.company',
                'sections' => [
                    [
                        'title' => 'ข้อมูลองค์กร',
                        'description' => 'แสดงบนแถบเมนู หน้าเข้าสู่ระบบ และหัวกระดาษเอกสาร',
                        'fields' => [
                            'name' => ['label' => 'ชื่อบริษัท', 'type' => 'text', 'config' => ['hrpro.company.name', 'hrpro.company_name'], 'rules' => ['required', 'string', 'max:150']],
                            'name_en' => ['label' => 'ชื่อบริษัท (ภาษาอังกฤษ)', 'type' => 'text', 'config' => 'hrpro.company.name_en', 'rules' => ['nullable', 'string', 'max:150']],
                            'tax_id' => ['label' => 'เลขประจำตัวผู้เสียภาษี', 'type' => 'text', 'config' => 'hrpro.company.tax_id', 'rules' => ['nullable', 'string', 'max:20'], 'placeholder' => '0105555000000'],
                            'phone' => ['label' => 'โทรศัพท์', 'type' => 'text', 'config' => 'hrpro.company.phone', 'rules' => ['nullable', 'string', 'max:40']],
                            'email' => ['label' => 'อีเมลติดต่อ', 'type' => 'email', 'config' => 'hrpro.company.email', 'rules' => ['nullable', 'email', 'max:150']],
                            'address' => ['label' => 'ที่อยู่', 'type' => 'textarea', 'config' => 'hrpro.company.address', 'rules' => ['nullable', 'string', 'max:500']],
                        ],
                    ],
                    [
                        'title' => 'หัวกระดาษและผู้ลงนาม',
                        'description' => 'ใช้กับสลิปเงินเดือน ใบลา หนังสือรับรอง และรายงานที่พิมพ์',
                        'fields' => [
                            'logo' => ['label' => 'โลโก้', 'type' => 'image', 'config' => 'hrpro.company.logo', 'rules' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'], 'hint' => 'PNG พื้นโปร่งใสดูดีที่สุด ไม่เกิน 1 MB · ถ้าไม่มีโลโก้ ระบบใช้อักษรย่อของชื่อบริษัทแทน'],
                            'color' => ['label' => 'สีประจำเอกสาร', 'type' => 'color', 'config' => 'hrpro.company.color', 'rules' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'], 'hint' => 'สีเส้นใต้หัวกระดาษและแถบหัวข้อในเอกสาร'],
                            'signatory_name' => ['label' => 'ชื่อผู้ลงนาม', 'type' => 'text', 'config' => 'hrpro.company.signatory_name', 'rules' => ['nullable', 'string', 'max:120'], 'hint' => 'พิมพ์ในวงเล็บใต้ลายเซ็นของหนังสือรับรองและสลิปเงินเดือน'],
                            'signatory_title' => ['label' => 'ตำแหน่งผู้ลงนาม', 'type' => 'text', 'config' => 'hrpro.company.signatory_title', 'rules' => ['nullable', 'string', 'max:120']],
                        ],
                    ],
                ],
            ],

            // The one group with no permission behind it: only a super-admin
            // may change how the whole system presents itself.
            'branding' => [
                'label' => 'โลโก้ระบบ',
                'description' => 'โลโก้บนเมนู หน้าเข้าสู่ระบบ ผังองค์กร และไอคอนแท็บเบราว์เซอร์',
                'icon' => 'image',
                'permission' => null,
                'super_admin_only' => true,
                'sections' => [
                    [
                        'title' => 'โลโก้ระบบ',
                        'description' => 'ใช้แทนตัวอักษร “HR” ทั่วทั้งระบบ · เฉพาะผู้ดูแลระบบสูงสุดเท่านั้นที่เห็นและแก้ไขหมวดนี้',
                        'fields' => [
                            'logo' => ['label' => 'โลโก้ระบบ', 'type' => 'image', 'config' => 'hrpro.branding.logo', 'rules' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'], 'hint' => 'แนะนำภาพสี่เหลี่ยมจัตุรัส PNG พื้นโปร่งใส อย่างน้อย 256×256 px ไม่เกิน 1 MB · แสดงบนเมนูด้านข้าง หน้าเข้าสู่ระบบ และผังองค์กร'],
                            'favicon' => ['label' => 'ไอคอนแท็บเบราว์เซอร์', 'type' => 'image', 'config' => 'hrpro.branding.favicon', 'rules' => ['nullable', 'image', 'mimes:png,webp', 'max:512'], 'empty' => 'ใช้โลโก้ระบบ', 'hint' => 'PNG สี่เหลี่ยมจัตุรัส เช่น 64×64 px · เว้นว่างเพื่อใช้โลโก้ระบบ'],
                            'use_on_documents' => ['label' => 'ใช้โลโก้ระบบบนหัวกระดาษเอกสาร เมื่อยังไม่ได้ตั้งโลโก้บริษัท', 'type' => 'toggle', 'config' => 'hrpro.branding.use_on_documents'],
                        ],
                    ],
                ],
            ],

            'work' => [
                'label' => 'เวลาทำงาน',
                'description' => 'เวลาเข้า–เลิกงาน วันทำงาน และช่วงผ่อนผัน',
                'icon' => 'clock',
                'permission' => 'settings.attendance',
                'sections' => [
                    [
                        'title' => 'เวลาทำงานปกติ',
                        'description' => 'ใช้ตัดสินสถานะมาสาย/ออกก่อนเวลา และนับวันลาเฉพาะวันทำงาน',
                        'fields' => [
                            'start' => ['label' => 'เวลาเข้างาน', 'type' => 'time', 'config' => 'hrpro.work.start', 'rules' => ['required', 'date_format:H:i']],
                            'end' => ['label' => 'เวลาเลิกงาน', 'type' => 'time', 'config' => 'hrpro.work.end', 'rules' => ['required', 'date_format:H:i', 'after:start']],
                            'working_days' => ['label' => 'วันทำงาน', 'type' => 'days', 'config' => 'hrpro.work.working_days', 'rules' => ['required', 'array', 'min:1'], 'hint' => 'วันที่ไม่ได้เลือกถือเป็นวันหยุด ไม่ถูกหักเป็นวันลาและไม่ขึ้นว่า “ไม่มีบันทึก”'],
                        ],
                    ],
                    [
                        'title' => 'ช่วงผ่อนผัน',
                        'fields' => [
                            'late_grace_minutes' => ['label' => 'มาหลังเวลาเข้างานได้ไม่เกิน', 'type' => 'number', 'unit' => 'นาที', 'config' => 'hrpro.work.late_grace_minutes', 'rules' => ['required', 'integer', 'between:0,240'], 'hint' => 'ภายในช่วงนี้ยังนับว่าตรงเวลา'],
                            'early_grace_minutes' => ['label' => 'ออกก่อนเวลาเลิกงานได้ไม่เกิน', 'type' => 'number', 'unit' => 'นาที', 'config' => 'hrpro.work.early_grace_minutes', 'rules' => ['required', 'integer', 'between:0,240'], 'hint' => 'ภายในช่วงนี้ไม่นับว่าออกก่อนเวลา'],
                        ],
                    ],
                ],
            ],

            'attendance' => [
                'label' => 'การลงเวลา',
                'description' => 'การตรวจตำแหน่ง GPS เหตุผลประกอบ และภาพถ่ายยืนยัน',
                'icon' => 'map-pin',
                'permission' => 'settings.attendance',
                'sections' => [
                    [
                        'title' => 'ตำแหน่ง GPS',
                        'description' => 'จุดและรัศมีที่อนุญาตกำหนดได้ที่หน้า “บริเวณลงเวลา”',
                        'fields' => [
                            'require_location' => ['label' => 'ตรวจพิกัดกับบริเวณที่อนุญาต', 'type' => 'toggle', 'config' => 'hrpro.attendance.require_location', 'hint' => 'มีผลเมื่อมีบริเวณลงเวลาอย่างน้อยหนึ่งแห่ง · ปิดไว้ ระบบบันทึกพิกัดเท่าที่ได้โดยไม่บังคับเปิดตำแหน่ง'],
                            'allow_off_site' => ['label' => 'อนุญาตให้ลงเวลานอกบริเวณ', 'type' => 'toggle', 'config' => 'hrpro.attendance.allow_off_site', 'hint' => 'เปิด: ลงเวลาได้และบันทึกว่า “นอกสถานที่” · ปิด: ต้องอยู่ในบริเวณจึงจะลงเวลาได้'],
                            'max_accuracy_meters' => ['label' => 'ความคลาดเคลื่อน GPS ที่ยอมรับ', 'type' => 'number', 'unit' => 'เมตร', 'config' => 'hrpro.attendance.max_accuracy_meters', 'rules' => ['required', 'integer', 'between:10,5000'], 'hint' => 'สัญญาณที่คลาดเคลื่อนมากกว่านี้จะลงเวลาไม่ได้'],
                        ],
                    ],
                    [
                        'title' => 'เหตุผลประกอบการลงเวลา',
                        'description' => 'เปิดเพื่อบังคับกรอก · ปิดไว้พนักงานเว้นว่างได้',
                        'fields' => [
                            'late_reason_required' => ['label' => 'บังคับกรอกเหตุผลเมื่อมาสาย', 'type' => 'toggle', 'config' => 'hrpro.attendance.late_reason_required'],
                            'early_reason_required' => ['label' => 'บังคับกรอกเหตุผลเมื่อออกก่อนเวลา', 'type' => 'toggle', 'config' => 'hrpro.attendance.early_reason_required'],
                            'off_site_reason_required' => ['label' => 'บังคับกรอกเหตุผลเมื่อลงเวลานอกสถานที่', 'type' => 'toggle', 'config' => 'hrpro.attendance.off_site_reason_required'],
                        ],
                    ],
                    [
                        'title' => 'ภาพถ่ายยืนยัน',
                        'description' => 'บทบาทที่ต้องถ่ายภาพตอนลงเวลา ตั้งได้ที่หน้า “บทบาท & สิทธิ์”',
                        'fields' => [
                            'photo_retention_days' => ['label' => 'เก็บภาพถ่ายลงเวลาไว้', 'type' => 'number', 'unit' => 'วัน', 'config' => 'hrpro.attendance.photo_retention_days', 'rules' => ['nullable', 'integer', 'between:1,3650'], 'placeholder' => 'ไม่จำกัด', 'hint' => 'ภาพที่เก่ากว่านี้จะถูกลบทุกคืน (ต้องตั้ง cron ของ schedule:run บนเซิร์ฟเวอร์) · เว้นว่างเพื่อเก็บไว้ตลอด'],
                        ],
                    ],
                ],
            ],

            'leave' => [
                'label' => 'การลา',
                'description' => 'เงื่อนไขการยื่นคำขอลาและไฟล์แนบ',
                'icon' => 'calendar',
                'permission' => 'settings.leave',
                'sections' => [
                    [
                        'title' => 'การยื่นคำขอลา',
                        'description' => 'จำนวนวันลาของแต่ละประเภท ตั้งได้ที่หน้า “ประเภทการลา”',
                        'fields' => [
                            'backdate_days' => ['label' => 'ยื่นลาย้อนหลังได้ไม่เกิน', 'type' => 'number', 'unit' => 'วัน', 'config' => 'hrpro.leave.backdate_days', 'rules' => ['nullable', 'integer', 'between:0,365'], 'placeholder' => 'ไม่จำกัด', 'hint' => '0 = ห้ามลาย้อนหลัง · เว้นว่าง = ไม่จำกัด'],
                            'attachment_max_mb' => ['label' => 'ขนาดไฟล์แนบสูงสุด', 'type' => 'number', 'unit' => 'MB', 'config' => 'hrpro.leave.attachment_max_mb', 'rules' => ['required', 'integer', 'between:1,20']],
                            'reason_required' => ['label' => 'บังคับกรอกเหตุผลการลา', 'type' => 'toggle', 'config' => 'hrpro.leave.reason_required'],
                        ],
                    ],
                ],
            ],

            'payroll' => [
                'label' => 'เงินเดือน',
                'description' => 'สูตรหักลาไม่รับค่าจ้าง ประกันสังคม และวันจ่ายเงิน',
                'icon' => 'wallet',
                'permission' => 'settings.payroll',
                'sections' => [
                    [
                        'title' => 'การคำนวณ',
                        'description' => 'มีผลกับการคำนวณงวดถัดไป งวดที่สร้างแล้วไม่เปลี่ยน',
                        'fields' => [
                            'days_per_month' => ['label' => 'จำนวนวันต่อเดือนสำหรับคิดค่าจ้างรายวัน', 'type' => 'number', 'unit' => 'วัน', 'config' => 'hrpro.payroll.days_per_month', 'rules' => ['required', 'integer', 'between:1,31'], 'hint' => 'เงินเดือน ÷ จำนวนนี้ = ค่าจ้างต่อวัน ใช้หักวันลาไม่รับค่าจ้าง'],
                            'pay_day' => ['label' => 'วันที่จ่ายเงินเดือน', 'type' => 'number', 'unit' => 'ของเดือน', 'config' => 'hrpro.payroll.pay_day', 'rules' => ['nullable', 'integer', 'between:1,31'], 'placeholder' => 'ไม่ระบุ', 'hint' => 'พิมพ์บนสลิปเงินเดือน · เดือนที่มีไม่ถึงวันนี้ใช้วันสุดท้ายของเดือน'],
                        ],
                    ],
                    [
                        'title' => 'ประกันสังคม',
                        'fields' => [
                            'social_security_enabled' => ['label' => 'หักเงินสมทบประกันสังคม', 'type' => 'toggle', 'config' => 'hrpro.payroll.social_security.enabled'],
                            'social_security_rate' => ['label' => 'อัตราเงินสมทบ', 'type' => 'number', 'unit' => '%', 'step' => '0.01', 'config' => 'hrpro.payroll.social_security.rate', 'rules' => ['required', 'numeric', 'between:0,20'], 'depends' => ['social_security_enabled' => '1']],
                            'social_security_wage_cap' => ['label' => 'ฐานค่าจ้างสูงสุด', 'type' => 'number', 'unit' => 'บาท', 'step' => '0.01', 'config' => 'hrpro.payroll.social_security.wage_cap', 'rules' => ['required', 'numeric', 'between:0,1000000'], 'depends' => ['social_security_enabled' => '1'], 'hint' => 'หัก = (ค่าจ้างหรือฐานนี้ แล้วแต่ค่าใดต่ำกว่า) × อัตรา · 0 = ไม่มีเพดาน · ปรับตามประกาศสำนักงานประกันสังคม'],
                        ],
                    ],
                    [
                        'title' => 'ค่าล่วงเวลา (OT)',
                        'description' => 'กรอกชั่วโมง OT ในสลิปฉบับร่าง ระบบคำนวณเงินให้',
                        'fields' => [
                            'hours_per_day' => ['label' => 'ชั่วโมงทำงานต่อวัน', 'type' => 'number', 'unit' => 'ชั่วโมง', 'step' => '0.5', 'config' => 'hrpro.payroll.hours_per_day', 'rules' => ['required', 'numeric', 'between:1,12'], 'hint' => 'ค่าจ้างต่อชั่วโมง = เงินเดือน ÷ จำนวนวันต่อเดือน ÷ ค่านี้'],
                            'overtime_rate' => ['label' => 'อัตราค่าล่วงเวลา', 'type' => 'number', 'unit' => 'เท่า', 'step' => '0.1', 'config' => 'hrpro.payroll.overtime_rate', 'rules' => ['required', 'numeric', 'between:1,5'], 'hint' => 'กฎหมายคุ้มครองแรงงานกำหนดไม่น้อยกว่า 1.5 เท่าสำหรับวันทำงานปกติ'],
                        ],
                    ],
                    [
                        'title' => 'ภาษีเงินได้หัก ณ ที่จ่าย',
                        'fields' => [
                            'withholding_tax_enabled' => ['label' => 'หักภาษี ณ ที่จ่ายทุกงวด', 'type' => 'toggle', 'config' => 'hrpro.payroll.withholding_tax.enabled'],
                            'withholding_tax_personal_allowance' => ['label' => 'ค่าลดหย่อนส่วนตัว', 'type' => 'number', 'unit' => 'บาท/ปี', 'config' => 'hrpro.payroll.withholding_tax.personal_allowance', 'rules' => ['required', 'numeric', 'between:0,1000000'], 'depends' => ['withholding_tax_enabled' => '1'], 'hint' => 'ประมาณการแบบเฉลี่ย 12 เดือน: หักค่าใช้จ่าย 50% (ไม่เกิน 100,000) ค่าลดหย่อนนี้ ประกันสังคม และกองทุนสำรองเลี้ยงชีพ แล้วคิดภาษีอัตราก้าวหน้า ÷ 12'],
                        ],
                    ],
                    [
                        'title' => 'กองทุนสำรองเลี้ยงชีพ',
                        'fields' => [
                            'provident_fund_enabled' => ['label' => 'หักเงินสะสมกองทุนสำรองเลี้ยงชีพ', 'type' => 'toggle', 'config' => 'hrpro.payroll.provident_fund.enabled'],
                            'provident_fund_rate' => ['label' => 'อัตราเงินสะสมของพนักงาน', 'type' => 'number', 'unit' => '%', 'step' => '0.5', 'config' => 'hrpro.payroll.provident_fund.rate', 'rules' => ['required', 'numeric', 'between:2,15'], 'depends' => ['provident_fund_enabled' => '1'], 'hint' => 'ตามกฎหมาย 2–15% ของค่าจ้าง'],
                        ],
                    ],
                ],
            ],

            'documents' => [
                'label' => 'คลังเอกสาร',
                'description' => 'ขนาดและชนิดไฟล์ที่อัปโหลดได้',
                'icon' => 'inbox',
                'permission' => 'settings.documents',
                'sections' => [
                    [
                        'title' => 'การอัปโหลดไฟล์',
                        'fields' => [
                            'max_upload_mb' => ['label' => 'ขนาดไฟล์สูงสุด', 'type' => 'number', 'unit' => 'MB', 'config' => 'hrpro.documents.max_upload_mb', 'rules' => ['required', 'integer', 'between:1,50'], 'hint' => 'ต้องไม่เกินค่า upload_max_filesize ของเซิร์ฟเวอร์'],
                            'allowed_types' => ['label' => 'ชนิดไฟล์ที่อนุญาต', 'type' => 'multi', 'config' => 'hrpro.documents.allowed_types', 'rules' => ['required', 'array', 'min:1'], 'options' => [
                                'pdf' => 'PDF', 'doc' => 'Word .doc', 'docx' => 'Word .docx', 'xls' => 'Excel .xls', 'xlsx' => 'Excel .xlsx',
                                'ppt' => 'PowerPoint .ppt', 'pptx' => 'PowerPoint .pptx', 'csv' => 'CSV', 'txt' => 'ข้อความ .txt',
                                'jpg' => 'JPG', 'jpeg' => 'JPEG', 'png' => 'PNG',
                            ]],
                        ],
                    ],
                ],
            ],

            'notifications' => [
                'label' => 'การแจ้งเตือน & อีเมล',
                'description' => 'การส่งอีเมลแจ้งเตือนและเซิร์ฟเวอร์อีเมล',
                'icon' => 'bell',
                'permission' => 'settings.notifications',
                'sections' => [
                    [
                        'title' => 'การแจ้งเตือน',
                        'fields' => [
                            'email_enabled' => ['label' => 'ส่งอีเมลเมื่อมีการแจ้งเตือน', 'type' => 'toggle', 'config' => 'hrpro.notifications.email', 'hint' => 'การแจ้งเตือนในระบบ (กระดิ่ง) ทำงานตามปกติเสมอ'],
                        ],
                    ],
                    [
                        'title' => 'ผู้ส่ง',
                        'fields' => [
                            'from_name' => ['label' => 'ชื่อผู้ส่ง', 'type' => 'text', 'config' => 'mail.from.name', 'rules' => ['required', 'string', 'max:120']],
                            'from_address' => ['label' => 'อีเมลผู้ส่ง', 'type' => 'email', 'config' => 'mail.from.address', 'rules' => ['required', 'email', 'max:150']],
                        ],
                    ],
                    [
                        'title' => 'เซิร์ฟเวอร์อีเมล',
                        'description' => 'ใช้ “บันทึกลงไฟล์” ระหว่างทดลองระบบ อีเมลจะไม่ถูกส่งออกจริง',
                        'fields' => [
                            'mailer' => ['label' => 'วิธีส่งอีเมล', 'type' => 'select', 'config' => 'mail.default', 'rules' => ['required'], 'options' => ['log' => 'บันทึกลงไฟล์ (ไม่ส่งจริง)', 'smtp' => 'SMTP']],
                            'smtp_host' => ['label' => 'SMTP Host', 'type' => 'text', 'config' => 'mail.mailers.smtp.host', 'rules' => ['nullable', 'required_if:mailer,smtp', 'string', 'max:190'], 'placeholder' => 'smtp.gmail.com', 'depends' => ['mailer' => 'smtp']],
                            'smtp_port' => ['label' => 'Port', 'type' => 'number', 'config' => 'mail.mailers.smtp.port', 'rules' => ['nullable', 'required_if:mailer,smtp', 'integer', 'between:1,65535'], 'placeholder' => '587', 'depends' => ['mailer' => 'smtp']],
                            'smtp_encryption' => ['label' => 'การเข้ารหัส', 'type' => 'select', 'config' => 'mail.mailers.smtp.encryption', 'rules' => ['nullable'], 'options' => ['tls' => 'TLS', 'ssl' => 'SSL', '' => 'ไม่เข้ารหัส'], 'depends' => ['mailer' => 'smtp']],
                            'smtp_username' => ['label' => 'ชื่อผู้ใช้', 'type' => 'text', 'config' => 'mail.mailers.smtp.username', 'rules' => ['nullable', 'string', 'max:190'], 'depends' => ['mailer' => 'smtp']],
                            'smtp_password' => ['label' => 'รหัสผ่าน', 'type' => 'password', 'secret' => true, 'config' => 'mail.mailers.smtp.password', 'rules' => ['nullable', 'string', 'max:190'], 'hint' => 'เก็บแบบเข้ารหัส · เว้นว่างเพื่อใช้รหัสเดิม', 'depends' => ['mailer' => 'smtp']],
                        ],
                    ],
                ],
            ],

            'security' => [
                'label' => 'ความปลอดภัย',
                'description' => 'นโยบายรหัสผ่านและการเข้าสู่ระบบ',
                'icon' => 'shield',
                'permission' => 'settings.security',
                'sections' => [
                    [
                        'title' => 'นโยบายรหัสผ่าน',
                        'description' => 'มีผลเมื่อสร้างผู้ใช้หรือเปลี่ยนรหัสผ่านครั้งถัดไป รหัสเดิมยังใช้ได้',
                        'fields' => [
                            'password_min_length' => ['label' => 'ความยาวขั้นต่ำ', 'type' => 'number', 'unit' => 'ตัวอักษร', 'config' => 'hrpro.security.password_min_length', 'rules' => ['required', 'integer', 'between:6,64']],
                            'password_letters_numbers' => ['label' => 'ต้องมีทั้งตัวอักษรและตัวเลข', 'type' => 'toggle', 'config' => 'hrpro.security.password_letters_numbers'],
                            'password_mixed_case' => ['label' => 'ต้องมีทั้งตัวพิมพ์ใหญ่และตัวพิมพ์เล็ก', 'type' => 'toggle', 'config' => 'hrpro.security.password_mixed_case'],
                            'password_symbols' => ['label' => 'ต้องมีสัญลักษณ์ เช่น ! @ #', 'type' => 'toggle', 'config' => 'hrpro.security.password_symbols'],
                        ],
                    ],
                    [
                        'title' => 'การเข้าสู่ระบบ',
                        'fields' => [
                            'login_max_attempts' => ['label' => 'กรอกรหัสผ่านผิดได้', 'type' => 'number', 'unit' => 'ครั้ง', 'config' => 'hrpro.security.login_max_attempts', 'rules' => ['required', 'integer', 'between:3,20'], 'hint' => 'เกินจำนวนนี้จะถูกพักการเข้าสู่ระบบชั่วคราว'],
                            'lockout_minutes' => ['label' => 'ระยะเวลาพักการเข้าสู่ระบบ', 'type' => 'number', 'unit' => 'นาที', 'config' => 'hrpro.security.lockout_minutes', 'rules' => ['required', 'integer', 'between:1,60']],
                            'session_lifetime' => ['label' => 'ออกจากระบบอัตโนมัติเมื่อไม่ได้ใช้งาน', 'type' => 'number', 'unit' => 'นาที', 'config' => 'session.lifetime', 'rules' => ['required', 'integer', 'between:5,1440']],
                        ],
                    ],
                ],
            ],

            'system' => [
                'label' => 'ระบบ',
                'description' => 'ชื่อระบบ ภาษา รหัสพนักงาน และค่าเริ่มต้นของบัญชีใหม่',
                'icon' => 'cog',
                'permission' => 'settings.system',
                'sections' => [
                    [
                        'title' => 'ทั่วไป',
                        'fields' => [
                            'app_name' => ['label' => 'ชื่อระบบ', 'type' => 'text', 'config' => 'app.name', 'rules' => ['required', 'string', 'max:60'], 'hint' => 'แสดงบนแท็บเบราว์เซอร์และหัวเรื่องอีเมล'],
                            'default_locale' => ['label' => 'ภาษาเริ่มต้น', 'type' => 'select', 'config' => 'hrpro.default_locale', 'rules' => ['required'], 'options' => ['th' => 'ไทย', 'en' => 'English']],
                            'per_page' => ['label' => 'จำนวนรายการต่อหน้า', 'type' => 'select', 'cast' => 'int', 'config' => 'hrpro.per_page', 'rules' => ['required'], 'options' => [10 => '10 รายการ', 15 => '15 รายการ', 25 => '25 รายการ', 50 => '50 รายการ', 100 => '100 รายการ']],
                        ],
                    ],
                    [
                        'title' => 'รหัสพนักงานอัตโนมัติ',
                        'description' => 'ใช้เมื่อเพิ่มพนักงานโดยไม่กรอกรหัส เช่น EMP- และ 4 หลัก ได้ EMP-0049',
                        'fields' => [
                            'employee_code_prefix' => ['label' => 'คำนำหน้ารหัส', 'type' => 'text', 'config' => 'hrpro.employee_code.prefix', 'rules' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9_\-\/]*$/'], 'hint' => 'ตัวอักษรอังกฤษ ตัวเลข และ - _ / เท่านั้น'],
                            'employee_code_pad' => ['label' => 'จำนวนหลักของเลขลำดับ', 'type' => 'number', 'unit' => 'หลัก', 'config' => 'hrpro.employee_code.pad', 'rules' => ['required', 'integer', 'between:1,10']],
                        ],
                    ],
                    [
                        'title' => 'การแสดงผลเริ่มต้น',
                        'description' => 'ใช้กับบัญชีที่สร้างใหม่และหน้าเข้าสู่ระบบ ผู้ใช้แต่ละคนเปลี่ยนของตัวเองได้ภายหลัง',
                        'fields' => [
                            'default_theme' => ['label' => 'ธีมสี', 'type' => 'select', 'config' => 'hrpro.appearance.theme', 'rules' => ['required'], 'options' => ['teal' => 'Teal', 'indigo' => 'Indigo', 'navy' => 'Navy', 'emerald' => 'Emerald', 'plum' => 'Plum']],
                            'default_scheme' => ['label' => 'โหมดการแสดงผล', 'type' => 'select', 'config' => 'hrpro.appearance.color_scheme', 'rules' => ['required'], 'options' => ['light' => 'สว่าง', 'dark' => 'มืด', 'auto' => 'ตามระบบของเครื่อง']],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Whether this account may open a group: a super-admin-only group checks
     * the role itself, so no permission handed to another role can open it.
     */
    public static function allows(\App\Models\User $user, array $group): bool
    {
        return ($group['super_admin_only'] ?? false)
            ? $user->isSuperAdmin()
            : $user->hasPermission($group['permission']);
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::groups());
    }

    public static function group(string $key): ?array
    {
        return self::groups()[$key] ?? null;
    }

    /**
     * The fields of one group, flattened out of its sections.
     *
     * @return array<string, array>
     */
    public static function groupFields(string $key): array
    {
        $fields = [];

        foreach (self::group($key)['sections'] ?? [] as $section) {
            $fields += $section['fields'];
        }

        return $fields;
    }

    /**
     * Every field keyed "group.field", as stored in the settings table.
     *
     * @return array<string, array>
     */
    public static function fields(): array
    {
        $flat = [];

        foreach (self::keys() as $group) {
            foreach (self::groupFields($group) as $name => $field) {
                $flat[$group.'.'.$name] = $field + ['group' => $group, 'name' => $name];
            }
        }

        return $flat;
    }
}
