<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PerformanceReview;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PerformanceReviewController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:performance.view', only: ['index', 'show']),
            new Middleware('permission:performance.manage', only: ['create', 'store', 'destroy']),
        ];
    }

    public function index(): View
    {
        $reviews = PerformanceReview::query()
            ->with(['employee:id,first_name,last_name,email', 'reviewer:id,name'])
            ->latest()
            ->paginate(config('hrpro.per_page'));

        return view('performance.index', ['reviews' => $reviews]);
    }

    public function create(): View
    {
        return view('performance.create', [
            'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name'])
                ->mapWithKeys(fn (Employee $e) => [$e->id => $e->full_name]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'period' => ['required', 'string', 'max:20'],
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'strengths' => ['nullable', 'string', 'max:1000'],
            'improvements' => ['nullable', 'string', 'max:1000'],
        ]);

        $review = PerformanceReview::create($data + [
            'reviewer_id' => $request->user()->id,
            'status' => 'submitted',
            'reviewed_at' => now(),
        ]);

        AuditLogger::log('created', "บันทึกผลประเมินงวด {$review->period}", $review);

        return redirect()->route('performance.show', $review)->with('success', 'บันทึกผลการประเมินเรียบร้อย');
    }

    public function show(PerformanceReview $performance): View
    {
        $performance->load(['employee.department', 'employee.position', 'reviewer']);

        return view('performance.show', ['review' => $performance]);
    }

    public function destroy(PerformanceReview $performance): RedirectResponse
    {
        $performance->delete();

        AuditLogger::log('deleted', "ลบผลประเมิน #{$performance->id}", $performance);

        return redirect()->route('performance.index')->with('success', 'ลบผลการประเมินเรียบร้อย');
    }
}
