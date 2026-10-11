<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permissions that impose a requirement rather than grant an ability.
     * They are kept out of the super-admin "everything" set, so ticking
     * them stays a deliberate choice for every role, super-admin included.
     */
    public const REQUIREMENTS = ['attendance.selfie-in', 'attendance.selfie-out'];

    /**
     * Canonical permission catalogue. The `name` values here are the single
     * source of truth referenced by controller middleware and Blade @can.
     *
     * @return array<int, array{0:string,1:string,2:string}>  [name, group, description]
     */
    public static function permissions(): array
    {
        return [
            ['employees.view', 'employees', 'ดูข้อมูลพนักงาน'],
            ['employees.create', 'employees', 'เพิ่มพนักงานใหม่'],
            ['employees.update', 'employees', 'แก้ไขข้อมูลพนักงาน'],
            ['employees.delete', 'employees', 'ลบพนักงาน'],

            ['departments.view', 'departments', 'ดูข้อมูลแผนก'],
            ['departments.create', 'departments', 'เพิ่มแผนก'],
            ['departments.update', 'departments', 'แก้ไขแผนก'],
            ['departments.delete', 'departments', 'ลบแผนก'],

            ['positions.view', 'positions', 'ดูตำแหน่งงาน'],
            ['positions.create', 'positions', 'เพิ่มตำแหน่งงาน'],
            ['positions.update', 'positions', 'แก้ไขตำแหน่งงาน'],
            ['positions.delete', 'positions', 'ลบตำแหน่งงาน'],

            ['leave-types.view', 'leave-types', 'ดูประเภทการลา'],
            ['leave-types.create', 'leave-types', 'เพิ่มประเภทการลา'],
            ['leave-types.update', 'leave-types', 'แก้ไขประเภทการลา'],
            ['leave-types.delete', 'leave-types', 'ลบประเภทการลา'],

            ['leave-requests.viewAll', 'leave-requests', 'ดูคำขอลาของพนักงานทุกคน'],
            ['leave-approvals.view', 'leave-approvals', 'ดูรายการรออนุมัติการลา'],
            ['leave-approvals.approve', 'leave-approvals', 'อนุมัติ/ปฏิเสธคำขอลา'],

            ['attendance.viewAll', 'attendance', 'ดูการลงเวลาของพนักงานทุกคน'],
            // Ticking one makes that role photograph itself with the front
            // camera at the punch. Off for every role until an admin opts in.
            ['attendance.selfie-in', 'attendance', 'บังคับถ่ายภาพหน้าตอนเข้างาน'],
            ['attendance.selfie-out', 'attendance', 'บังคับถ่ายภาพหน้าตอนออกงาน'],

            ['work-locations.view', 'work-locations', 'ดูบริเวณสำหรับลงเวลา'],
            ['work-locations.create', 'work-locations', 'เพิ่มบริเวณสำหรับลงเวลา'],
            ['work-locations.update', 'work-locations', 'แก้ไขบริเวณสำหรับลงเวลา'],
            ['work-locations.delete', 'work-locations', 'ลบบริเวณสำหรับลงเวลา'],

            ['announcements.manage', 'announcements', 'จัดการประกาศ (เพิ่ม/แก้ไข/ลบ)'],

            ['reports.view', 'reports', 'ดูรายงานและสถิติ'],
            ['audit-logs.view', 'audit-logs', 'ดูบันทึกการใช้งานระบบ'],

            ['payroll.view', 'payroll', 'ดูข้อมูลเงินเดือน'],
            ['payroll.manage', 'payroll', 'จัดการ/คำนวณเงินเดือน'],

            ['recruitment.view', 'recruitment', 'ดูประกาศรับสมัครและผู้สมัคร'],
            ['recruitment.manage', 'recruitment', 'จัดการการสรรหา'],

            ['performance.view', 'performance', 'ดูผลการประเมิน'],
            ['performance.manage', 'performance', 'จัดการการประเมินผล'],

            ['training.view', 'training', 'ดูหลักสูตรฝึกอบรม'],
            ['training.manage', 'training', 'จัดการหลักสูตร/การลงทะเบียน'],

            ['documents.view', 'documents', 'ดูคลังเอกสาร'],
            ['documents.manage', 'documents', 'จัดการคลังเอกสาร'],

            // One per group on the settings screen (App\Support\SettingsCatalogue).
            ['settings.company', 'settings', 'ตั้งค่าข้อมูลบริษัทและหัวกระดาษเอกสาร'],
            ['settings.attendance', 'settings', 'ตั้งค่าเวลาทำงานและการลงเวลา'],
            ['settings.leave', 'settings', 'ตั้งค่าการลา'],
            ['settings.payroll', 'settings', 'ตั้งค่าการคำนวณเงินเดือน'],
            ['settings.documents', 'settings', 'ตั้งค่าคลังเอกสาร'],
            ['settings.notifications', 'settings', 'ตั้งค่าการแจ้งเตือนและอีเมล'],
            ['settings.security', 'settings', 'ตั้งค่าความปลอดภัยและรหัสผ่าน'],
            ['settings.system', 'settings', 'ตั้งค่าระบบ'],

            ['users.view', 'users', 'ดูผู้ใช้งานระบบ'],
            ['users.create', 'users', 'เพิ่มผู้ใช้งานระบบ'],
            ['users.update', 'users', 'แก้ไขผู้ใช้งานระบบ'],
            ['users.delete', 'users', 'ลบผู้ใช้งานระบบ'],

            ['roles.view', 'roles', 'ดูบทบาทและสิทธิ์'],
            ['roles.create', 'roles', 'เพิ่มบทบาท'],
            ['roles.update', 'roles', 'แก้ไขบทบาทและสิทธิ์'],
            ['roles.delete', 'roles', 'ลบบทบาท'],
        ];
    }

    public function run(): void
    {
        foreach (self::permissions() as [$name, $group, $description]) {
            Permission::updateOrCreate(['name' => $name], ['group' => $group, 'description' => $description]);
        }

        $hrManager = [
            'employees.view', 'employees.create', 'employees.update', 'employees.delete',
            'departments.view', 'departments.create', 'departments.update', 'departments.delete',
            'positions.view', 'positions.create', 'positions.update', 'positions.delete',
            'leave-types.view', 'leave-types.create', 'leave-types.update', 'leave-types.delete',
            'leave-requests.viewAll', 'leave-approvals.view', 'leave-approvals.approve',
            'attendance.viewAll',
            'work-locations.view', 'work-locations.create', 'work-locations.update', 'work-locations.delete',
            'announcements.manage', 'reports.view', 'audit-logs.view',
            'payroll.view', 'payroll.manage',
            'recruitment.view', 'recruitment.manage',
            'performance.view', 'performance.manage',
            'training.view', 'training.manage',
            'documents.view', 'documents.manage',
            // HR runs the organisation's day-to-day rules; security, email and
            // system-wide settings stay with the super-admin.
            'settings.company', 'settings.attendance', 'settings.leave', 'settings.payroll', 'settings.documents',
        ];

        // A supervisor approves leave and reads aggregate reports, but the
        // attendance log itself is personal data: only the administrator tier
        // (super-admin, and HR who runs payroll from it) reads everyone's.
        $manager = [
            'employees.view', 'leave-requests.viewAll', 'leave-approvals.view',
            'leave-approvals.approve', 'work-locations.view', 'reports.view',
            'performance.view', 'training.view', 'recruitment.view',
        ];

        $roles = [
            ['super-admin', 'ผู้ดูแลระบบสูงสุด', 'เข้าถึงได้ทุกฟังก์ชันของระบบ', true, '*'],
            ['hr-manager', 'ผู้จัดการฝ่ายบุคคล', 'จัดการข้อมูลพนักงาน การลา และประกาศ', false, $hrManager],
            ['manager', 'หัวหน้างาน', 'ดูข้อมูลทีมและอนุมัติการลา', false, $manager],
            ['employee', 'พนักงาน', 'ใช้งานบริการตนเอง (ลา / ลงเวลา / โปรไฟล์)', true, []],
        ];

        foreach ($roles as [$slug, $name, $description, $isSystem, $perms]) {
            $role = Role::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $description, 'is_system' => $isSystem],
            );

            $ids = $perms === '*'
                ? Permission::whereNotIn('name', self::REQUIREMENTS)->pluck('id')->all()
                : Permission::whereIn('name', $perms)->pluck('id')->all();

            $role->syncPermissions($ids);
        }
    }
}
