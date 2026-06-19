<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
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

            ['announcements.manage', 'announcements', 'จัดการประกาศ (เพิ่ม/แก้ไข/ลบ)'],

            ['reports.view', 'reports', 'ดูรายงานและสถิติ'],
            ['audit-logs.view', 'audit-logs', 'ดูบันทึกการใช้งานระบบ'],

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
            'attendance.viewAll', 'announcements.manage', 'reports.view', 'audit-logs.view',
        ];

        $manager = [
            'employees.view', 'leave-requests.viewAll', 'leave-approvals.view',
            'leave-approvals.approve', 'attendance.viewAll', 'reports.view',
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
                ? Permission::pluck('id')->all()
                : Permission::whereIn('name', $perms)->pluck('id')->all();

            $role->syncPermissions($ids);
        }
    }
}
