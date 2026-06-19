<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
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

        // --- Attendance for today -----------------------------------------
        foreach ($allEmployees->random(30) as $emp) {
            $clockIn = now()->setTime(fake()->numberBetween(8, 9), fake()->numberBetween(0, 59));
            $clockOut = (clone $clockIn)->addHours(8)->addMinutes(fake()->numberBetween(0, 45));
            Attendance::create([
                'employee_id' => $emp->id,
                'work_date' => now()->toDateString(),
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'worked_minutes' => $clockIn->diffInMinutes($clockOut),
                'status' => $clockIn->hour >= 9 && $clockIn->minute > 15 ? 'late' : 'present',
            ]);
        }

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
    }
}
