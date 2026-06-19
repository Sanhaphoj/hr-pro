<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $year = now()->year;

        // --- Departments --------------------------------------------------
        $departmentDefs = [
            ['ทรัพยากรบุคคล', 'DPT-HR'],
            ['การเงินและบัญชี', 'DPT-FIN'],
            ['เทคโนโลยีสารสนเทศ', 'DPT-IT'],
            ['การตลาด', 'DPT-MKT'],
            ['ฝ่ายขาย', 'DPT-SAL'],
            ['ปฏิบัติการ', 'DPT-OPS'],
        ];
        $departments = collect($departmentDefs)->map(fn ($d) => Department::create([
            'name' => $d[0],
            'code' => $d[1],
            'description' => "แผนก{$d[0]}",
            'is_active' => true,
        ]));

        // --- Positions ----------------------------------------------------
        $positionDefs = [
            ['ผู้อำนวยการฝ่าย', 'executive'],
            ['ผู้จัดการ', 'manager'],
            ['หัวหน้าทีม', 'lead'],
            ['เจ้าหน้าที่อาวุโส', 'senior'],
            ['เจ้าหน้าที่', 'mid'],
            ['เจ้าหน้าที่ฝึกหัด', 'junior'],
        ];
        $positions = collect();
        $seq = 1;
        foreach ($departments as $dept) {
            foreach ($positionDefs as $p) {
                $positions->push(Position::create([
                    'title' => $p[0],
                    'code' => 'POS-'.str_pad((string) $seq++, 3, '0', STR_PAD_LEFT),
                    'department_id' => $dept->id,
                    'level' => $p[1],
                    'is_active' => true,
                ]));
            }
        }

        // --- Leave types --------------------------------------------------
        $leaveTypeDefs = [
            ['ลาพักร้อน', 'ANNUAL', 10, true, true, 'green'],
            ['ลาป่วย', 'SICK', 30, true, true, 'red'],
            ['ลากิจส่วนตัว', 'PERSONAL', 6, true, true, 'blue'],
            ['ลาคลอด', 'MATERNITY', 98, true, true, 'amber'],
            ['ลาไม่รับค่าจ้าง', 'UNPAID', 0, true, false, 'gray'],
        ];
        $leaveTypes = collect($leaveTypeDefs)->map(fn ($t) => LeaveType::create([
            'name' => $t[0], 'code' => $t[1], 'days_per_year' => $t[2],
            'requires_approval' => $t[3], 'is_paid' => $t[4], 'color' => $t[5],
            'description' => "ประเภท{$t[0]}", 'is_active' => true,
        ]));

        // --- Roles --------------------------------------------------------
        $roles = Role::pluck('id', 'slug');

        // --- Demo accounts ------------------------------------------------
        $accounts = [
            ['ผู้ดูแลระบบ HR PRO', 'admin@hrpro.local', 'super-admin'],
            ['สมหญิง ฝ่ายบุคคล', 'hr@hrpro.local', 'hr-manager'],
            ['สมชาย หัวหน้าทีม', 'manager@hrpro.local', 'manager'],
            ['พนักงาน ทดลอง', 'employee@hrpro.local', 'employee'],
        ];
        $demoUsers = [];
        foreach ($accounts as [$name, $email, $slug]) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $user->roles()->sync([$roles[$slug]]);
            $demoUsers[$slug] = $user;
        }

        // --- Employee profile for the demo employee account ---------------
        $itDept = $departments->firstWhere('code', 'DPT-IT');
        $demoEmployee = Employee::factory()->create([
            'user_id' => $demoUsers['employee']->id,
            'first_name' => 'พนักงาน',
            'last_name' => 'ทดลอง',
            'email' => 'employee@hrpro.local',
            'department_id' => $itDept->id,
            'position_id' => $positions->where('department_id', $itDept->id)->firstWhere('level', 'mid')?->id,
            'status' => 'active',
            'employment_type' => 'full_time',
            'hire_date' => now()->subYears(2)->toDateString(),
        ]);

        // Profile for the HR manager too
        $hrDept = $departments->firstWhere('code', 'DPT-HR');
        Employee::factory()->create([
            'user_id' => $demoUsers['hr-manager']->id,
            'first_name' => 'สมหญิง',
            'last_name' => 'ฝ่ายบุคคล',
            'email' => 'hr@hrpro.local',
            'department_id' => $hrDept->id,
            'position_id' => $positions->where('department_id', $hrDept->id)->firstWhere('level', 'manager')?->id,
            'status' => 'active',
            'hire_date' => now()->subYears(4)->toDateString(),
        ]);

        // --- Bulk employees ------------------------------------------------
        $employees = Employee::factory(45)->make()->each(function (Employee $emp) use ($departments, $positions) {
            $dept = $departments->random();
            $emp->department_id = $dept->id;
            $emp->position_id = $positions->where('department_id', $dept->id)->random()->id;
            $emp->save();
        });

        // Assign a department manager from within each department
        foreach ($departments as $dept) {
            $head = Employee::where('department_id', $dept->id)->inRandomOrder()->first();
            if ($head) {
                $dept->update(['manager_id' => $head->id]);
            }
        }

        $allEmployees = Employee::all();

        // --- Leave balances for everyone ----------------------------------
        foreach ($allEmployees as $emp) {
            foreach ($leaveTypes as $type) {
                if ($type->days_per_year <= 0) {
                    continue;
                }
                $used = fake()->randomElement([0, 0, 1, 2, 3]);
                LeaveBalance::create([
                    'employee_id' => $emp->id,
                    'leave_type_id' => $type->id,
                    'year' => $year,
                    'entitled_days' => $type->days_per_year,
                    'used_days' => min($used, $type->days_per_year),
                    'pending_days' => 0,
                ]);
            }
        }

        // --- Sample leave requests for the demo employee ------------------
        $annual = $leaveTypes->firstWhere('code', 'ANNUAL');
        $sick = $leaveTypes->firstWhere('code', 'SICK');

        LeaveRequest::create([
            'employee_id' => $demoEmployee->id,
            'leave_type_id' => $annual->id,
            'start_date' => now()->addDays(7)->next(Carbon::MONDAY)->toDateString(),
            'end_date' => now()->addDays(7)->next(Carbon::MONDAY)->addDays(2)->toDateString(),
            'total_days' => 3,
            'reason' => 'พาครอบครัวไปต่างจังหวัด',
            'status' => 'pending',
        ]);
        LeaveBalance::where(['employee_id' => $demoEmployee->id, 'leave_type_id' => $annual->id, 'year' => $year])
            ->increment('pending_days', 3);

        LeaveRequest::create([
            'employee_id' => $demoEmployee->id,
            'leave_type_id' => $sick->id,
            'start_date' => now()->subDays(14)->toDateString(),
            'end_date' => now()->subDays(14)->toDateString(),
            'total_days' => 1,
            'reason' => 'ไม่สบาย เป็นไข้',
            'status' => 'approved',
            'approver_id' => $demoUsers['hr-manager']->id,
            'approved_at' => now()->subDays(15),
        ]);

        // A few pending requests from random employees (for the approval queue)
        foreach ($allEmployees->random(6) as $emp) {
            $start = now()->addDays(fake()->numberBetween(3, 20));
            LeaveRequest::create([
                'employee_id' => $emp->id,
                'leave_type_id' => $leaveTypes->whereIn('code', ['ANNUAL', 'PERSONAL', 'SICK'])->random()->id,
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDay()->toDateString(),
                'total_days' => 2,
                'reason' => fake()->sentence(),
                'status' => 'pending',
            ]);
        }

        // --- Varied historical leave requests (approved/rejected/cancelled) ---
        $personal = $leaveTypes->firstWhere('code', 'PERSONAL');

        LeaveRequest::create([
            'employee_id' => $demoEmployee->id,
            'leave_type_id' => $personal->id,
            'start_date' => now()->subDays(40)->toDateString(),
            'end_date' => now()->subDays(40)->toDateString(),
            'total_days' => 1,
            'reason' => 'ติดธุระส่วนตัว',
            'status' => 'rejected',
            'approver_id' => $demoUsers['hr-manager']->id,
            'approved_at' => now()->subDays(41),
            'rejection_reason' => 'ช่วงเวลาดังกล่าวมีงานเร่งด่วน กรุณาเลือกวันอื่น',
        ]);

        LeaveRequest::create([
            'employee_id' => $demoEmployee->id,
            'leave_type_id' => $annual->id,
            'start_date' => now()->subDays(70)->toDateString(),
            'end_date' => now()->subDays(68)->toDateString(),
            'total_days' => 3,
            'reason' => 'ท่องเที่ยวต่างประเทศ (ยกเลิกแล้ว)',
            'status' => 'cancelled',
        ]);

        foreach ($allEmployees->random(14) as $emp) {
            $type = $leaveTypes->whereIn('code', ['ANNUAL', 'SICK', 'PERSONAL'])->random();
            $startAt = now()->subDays(fake()->numberBetween(15, 150));
            $days = fake()->numberBetween(1, 3);
            $status = fake()->randomElement(['approved', 'approved', 'approved', 'rejected']);
            LeaveRequest::create([
                'employee_id' => $emp->id,
                'leave_type_id' => $type->id,
                'start_date' => $startAt->toDateString(),
                'end_date' => $startAt->copy()->addDays($days - 1)->toDateString(),
                'total_days' => $days,
                'reason' => fake()->sentence(),
                'status' => $status,
                'approver_id' => $demoUsers['hr-manager']->id,
                'approved_at' => $startAt->copy()->subDays(2),
                'rejection_reason' => $status === 'rejected' ? 'ไม่อนุมัติเนื่องจากกำลังคนไม่เพียงพอในช่วงดังกล่าว' : null,
            ]);
        }

        // --- Attendance history (past ~2 weeks of working days) -----------
        $historyEmployees = $allEmployees
            ->where('id', '!=', $demoEmployee->id)
            ->random(min(28, max($allEmployees->count() - 1, 1)));
        foreach ($historyEmployees as $emp) {
            $this->seedAttendanceHistory($emp, true);
        }
        // Demo employee: seed past days but leave today open for a live clock-in demo.
        $this->seedAttendanceHistory($demoEmployee, false);

        // --- Announcements -------------------------------------------------
        $admin = $demoUsers['super-admin'];
        $announcements = [
            ['ยินดีต้อนรับสู่ระบบ HR PRO', 'general', true],
            ['ประกาศวันหยุดประจำปี 2569', 'policy', true],
            ['กิจกรรมสัมมนาประจำปีของบริษัท', 'event', false],
            ['แจ้งปรับปรุงนโยบายการลาพักร้อน', 'policy', false],
            ['ด่วน: ซ้อมหนีไฟวันศุกร์นี้ เวลา 14:00 น.', 'urgent', false],
        ];
        foreach ($announcements as $i => [$title, $category, $pinned]) {
            Announcement::create([
                'title' => $title,
                'body' => fake()->paragraphs(3, true),
                'category' => $category,
                'is_published' => true,
                'published_at' => now()->subDays($i),
                'author_id' => $admin->id,
                'pinned' => $pinned,
            ]);
        }

        // --- In-app notifications -----------------------------------------
        $employeeUser = $demoUsers['employee'];
        $hrUser = $demoUsers['hr-manager'];
        $notifications = [
            [$employeeUser->id, 'success', 'อนุมัติการลา', 'คำขอลาป่วยของคุณได้รับการอนุมัติแล้ว', '/leave-requests', now()->subDays(15)],
            [$employeeUser->id, 'error', 'ปฏิเสธการลา', 'คำขอลากิจของคุณไม่ได้รับการอนุมัติ กรุณาดูเหตุผล', '/leave-requests', now()->subDays(41)],
            [$employeeUser->id, 'info', 'ยินดีต้อนรับ', 'ยินดีต้อนรับเข้าสู่ระบบ HR PRO เริ่มต้นใช้งานได้จากเมนูด้านซ้าย', null, null],
            [$hrUser->id, 'warning', 'มีคำขอลารออนุมัติ', 'มีคำขอลารอการอนุมัติในระบบ กรุณาตรวจสอบที่เมนูอนุมัติการลา', '/leave-approvals', null],
            [$hrUser->id, 'info', 'พนักงานเข้าใหม่', 'มีการเพิ่มข้อมูลพนักงานใหม่เข้าสู่ระบบ', '/employees', now()->subDays(2)],
        ];
        foreach ($notifications as [$uid, $type, $title, $message, $link, $readAt]) {
            Notification::create([
                'user_id' => $uid,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'read_at' => $readAt,
            ]);
        }

        // --- Audit log trail ----------------------------------------------
        $auditEntries = [
            [$admin->id, 'login', 'เข้าสู่ระบบ (admin@hrpro.local)', now()->subHours(2)],
            [$hrUser->id, 'login', 'เข้าสู่ระบบ (hr@hrpro.local)', now()->subHours(5)],
            [$hrUser->id, 'created', 'เพิ่มพนักงานใหม่เข้าสู่ระบบ', now()->subDays(2)],
            [$hrUser->id, 'approved', 'อนุมัติคำขอลางาน', now()->subDays(3)],
            [$hrUser->id, 'rejected', 'ปฏิเสธคำขอลางาน', now()->subDays(4)],
            [$admin->id, 'created', 'สร้างประกาศ: ยินดีต้อนรับสู่ระบบ HR PRO', now()->subDays(5)],
            [$admin->id, 'updated', 'แก้ไขสิทธิ์ของบทบาท "หัวหน้างาน"', now()->subDays(6)],
            [$employeeUser->id, 'created', 'ส่งคำขอลางาน', now()->subDays(14)],
            [$employeeUser->id, 'updated', 'แก้ไขข้อมูลโปรไฟล์ส่วนตัว', now()->subDays(10)],
        ];
        foreach ($auditEntries as [$uid, $action, $description, $when]) {
            $log = AuditLog::create([
                'user_id' => $uid,
                'action' => $action,
                'description' => $description,
                'ip_address' => fake()->ipv4(),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) HR PRO Demo',
            ]);
            $log->forceFill(['created_at' => $when, 'updated_at' => $when])->save();
        }
    }

    /**
     * Create attendance rows for the past ~2 weeks of weekdays for one employee.
     * Includes occasional lateness/absence, and (optionally) today's record.
     */
    private function seedAttendanceHistory(Employee $employee, bool $includeToday): void
    {
        for ($d = $includeToday ? 0 : 1; $d <= 14; $d++) {
            $date = now()->subDays($d);
            if ($date->isWeekend()) {
                continue;
            }

            // Occasional absence on past days.
            if ($d > 0 && fake()->boolean(8)) {
                Attendance::create([
                    'employee_id' => $employee->id,
                    'work_date' => $date->toDateString(),
                    'status' => 'absent',
                    'worked_minutes' => 0,
                ]);
                continue;
            }

            $clockIn = $date->copy()->setTime(fake()->numberBetween(8, 9), fake()->numberBetween(0, 59));
            $stillWorking = $d === 0 && fake()->boolean(35); // some haven't clocked out yet today
            $clockOut = $stillWorking ? null : (clone $clockIn)->addHours(8)->addMinutes(fake()->numberBetween(0, 55));
            $late = $clockIn->hour > 9 || ($clockIn->hour === 9 && $clockIn->minute > 15);

            Attendance::create([
                'employee_id' => $employee->id,
                'work_date' => $date->toDateString(),
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'worked_minutes' => $clockOut ? (int) $clockIn->diffInMinutes($clockOut) : 0,
                'status' => $late ? 'late' : 'present',
            ]);
        }
    }
}
