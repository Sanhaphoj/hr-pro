<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class DepartmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:departments.view', only: ['index']),
            new Middleware('permission:departments.create', only: ['create', 'store']),
            new Middleware('permission:departments.update', only: ['edit', 'update']),
            new Middleware('permission:departments.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = $request->query('search');

        $departments = Department::query()
            ->with('manager')
            ->withCount('employees')
            ->search($search)
            ->orderBy('name')
            ->paginate(config('hrpro.per_page'))
            ->withQueryString();

        return view('departments.index', [
            'departments' => $departments,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('departments.create', [
            'parents' => $this->parentOptions(),
            'managers' => $this->managerOptions(),
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $department = Department::create($request->validated());

        AuditLogger::log('created', "เพิ่มแผนก: {$department->name}", $department);

        return redirect()->route('departments.index')->with('success', 'เพิ่มแผนกเรียบร้อยแล้ว');
    }

    public function edit(Department $department): View
    {
        return view('departments.edit', [
            'department' => $department,
            'parents' => $this->parentOptions($department->id),
            'managers' => $this->managerOptions(),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        AuditLogger::log('updated', "แก้ไขแผนก: {$department->name}", $department);

        return redirect()->route('departments.index')->with('success', 'บันทึกข้อมูลแผนกเรียบร้อยแล้ว');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $name = $department->name;
        $department->delete();

        AuditLogger::log('deleted', "ลบแผนก: {$name}", $department);

        return redirect()->route('departments.index')->with('success', 'ลบแผนกเรียบร้อยแล้ว');
    }

    /**
     * Options for the parent-department select, excluding the department itself.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function parentOptions(?int $exceptId = null)
    {
        return Department::query()
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Options for the manager select keyed by employee id with the full name as label.
     *
     * @return array<int, string>
     */
    private function managerOptions(): array
    {
        return Employee::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (Employee $e) => [$e->id => $e->full_name])
            ->all();
    }
}
