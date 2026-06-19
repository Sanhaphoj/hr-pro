<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class EmployeeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:employees.view', only: ['index', 'show']),
            new Middleware('permission:employees.create', only: ['create', 'store']),
            new Middleware('permission:employees.update', only: ['edit', 'update']),
            new Middleware('permission:employees.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $employees = Employee::query()
            ->with(['department', 'position'])
            ->search($request->query('q'))
            ->when($request->filled('department_id'), function ($query) use ($request) {
                $query->where('department_id', $request->query('department_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->query('status'));
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(config('hrpro.per_page'))
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->pluck('name', 'id'),
            'statuses' => $this->statusOptions(),
            'filters' => [
                'q' => $request->query('q'),
                'department_id' => $request->query('department_id'),
                'status' => $request->query('status'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('employees.create', $this->formData());
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['employee_code'] ?? null)) {
            $data['employee_code'] = config('hrpro.employee_code.prefix')
                .str_pad((string) (Employee::max('id') + 1), config('hrpro.employee_code.pad'), '0', STR_PAD_LEFT);
        }

        $employee = Employee::create($data);

        AuditLogger::log('created', "เพิ่มพนักงาน {$employee->full_name}", $employee);

        return redirect()->route('employees.index')->with('success', 'เพิ่มพนักงานเรียบร้อยแล้ว');
    }

    public function show(Employee $employee): View
    {
        $employee->load(['department', 'position', 'manager', 'user']);

        $year = (int) now()->year;

        $balances = $employee->leaveBalances()
            ->with('leaveType')
            ->where('year', $year)
            ->get();

        $leaveRequests = $employee->leaveRequests()
            ->with('leaveType')
            ->orderByDesc('start_date')
            ->limit(10)
            ->get();

        return view('employees.show', [
            'employee' => $employee,
            'balances' => $balances,
            'leaveRequests' => $leaveRequests,
            'year' => $year,
        ]);
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', array_merge(
            $this->formData($employee),
            ['employee' => $employee],
        ));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->fill($request->validated());
        $employee->save();

        AuditLogger::log('updated', "แก้ไขข้อมูลพนักงาน {$employee->full_name}", $employee);

        return redirect()->route('employees.show', $employee)->with('success', 'บันทึกข้อมูลพนักงานเรียบร้อยแล้ว');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $name = $employee->full_name;
        $employee->delete();

        AuditLogger::log('deleted', "ลบพนักงาน {$name}", $employee);

        return redirect()->route('employees.index')->with('success', 'ลบพนักงานเรียบร้อยแล้ว');
    }

    /**
     * Shared option data for the create/edit forms.
     */
    protected function formData(?Employee $employee = null): array
    {
        $managers = Employee::query()
            ->when($employee, fn ($query) => $query->whereKeyNot($employee->getKey()))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (Employee $e) => [$e->id => $e->full_name]);

        return [
            'departments' => Department::active()->orderBy('name')->pluck('name', 'id'),
            'positions' => Position::active()->orderBy('title')->pluck('title', 'id'),
            'managers' => $managers,
            'genders' => $this->genderOptions(),
            'employmentTypes' => $this->employmentTypeOptions(),
            'statuses' => $this->statusOptions(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function statusOptions(): array
    {
        return [
            'active' => 'ทำงานปกติ',
            'probation' => 'ทดลองงาน',
            'on_leave' => 'กำลังลา',
            'suspended' => 'พักงาน',
            'terminated' => 'พ้นสภาพ',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function employmentTypeOptions(): array
    {
        return [
            'full_time' => 'พนักงานประจำ',
            'part_time' => 'พาร์ทไทม์',
            'contract' => 'สัญญาจ้าง',
            'intern' => 'นักศึกษาฝึกงาน',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function genderOptions(): array
    {
        return [
            'male' => 'ชาย',
            'female' => 'หญิง',
            'other' => 'อื่น ๆ',
        ];
    }
}
