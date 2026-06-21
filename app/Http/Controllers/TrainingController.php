<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TrainingCourse;
use App\Models\TrainingEnrollment;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class TrainingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:training.view', only: ['index', 'show']),
            new Middleware('permission:training.manage', only: ['create', 'store', 'destroy', 'enroll', 'complete']),
        ];
    }

    public function index(): View
    {
        $courses = TrainingCourse::query()
            ->withCount('enrollments')
            ->latest()
            ->paginate(config('hrpro.per_page'));

        return view('training.index', ['courses' => $courses]);
    }

    public function create(): View
    {
        return view('training.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'hours' => ['required', 'integer', 'min:0', 'max:999'],
            'scheduled_date' => ['nullable', 'date'],
        ]);

        $course = TrainingCourse::create($data + ['is_active' => true]);

        AuditLogger::log('created', "เพิ่มหลักสูตร: {$course->title}", $course);

        return redirect()->route('training.show', $course)->with('success', 'เพิ่มหลักสูตรเรียบร้อย');
    }

    public function show(TrainingCourse $training): View
    {
        $training->load(['enrollments.employee:id,first_name,last_name,email']);

        $enrolledIds = $training->enrollments->pluck('employee_id');
        $employees = Employee::orderBy('first_name')
            ->whereNotIn('id', $enrolledIds)
            ->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (Employee $e) => [$e->id => $e->full_name]);

        return view('training.show', [
            'course' => $training,
            'employees' => $employees,
        ]);
    }

    public function enroll(Request $request, TrainingCourse $training): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
        ]);

        TrainingEnrollment::firstOrCreate(
            ['training_course_id' => $training->id, 'employee_id' => $data['employee_id']],
            ['status' => 'enrolled'],
        );

        return back()->with('success', 'ลงทะเบียนผู้เข้าอบรมเรียบร้อย');
    }

    public function complete(TrainingCourse $training, TrainingEnrollment $enrollment): RedirectResponse
    {
        abort_unless($enrollment->training_course_id === $training->id, 404);

        $enrollment->update(['status' => 'completed', 'completed_at' => now()]);

        return back()->with('success', 'บันทึกว่าจบหลักสูตรเรียบร้อย');
    }

    public function destroy(TrainingCourse $training): RedirectResponse
    {
        $title = $training->title;
        $training->delete();

        AuditLogger::log('deleted', "ลบหลักสูตร: {$title}", $training);

        return redirect()->route('training.index')->with('success', 'ลบหลักสูตรเรียบร้อย');
    }
}
