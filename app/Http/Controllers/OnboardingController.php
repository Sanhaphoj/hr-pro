<?php

namespace App\Http\Controllers;

use App\Models\OnboardingTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    /**
     * Self-service onboarding checklist for the logged-in employee.
     * Open to any authenticated, active user (no extra permission).
     */
    public function index(): View
    {
        $employee = auth()->user()->employee;

        $tasks = collect();
        if ($employee) {
            // Seed the checklist from the template on first visit.
            if (! $employee->onboardingTasks()->exists()) {
                foreach (OnboardingTask::TEMPLATE as $i => [$title, $description]) {
                    $employee->onboardingTasks()->create([
                        'title' => $title,
                        'description' => $description,
                        'sort_order' => $i,
                    ]);
                }
            }

            $tasks = $employee->onboardingTasks()->orderBy('sort_order')->get();
        }

        $done = $tasks->where('is_done', true)->count();
        $progress = $tasks->isNotEmpty() ? (int) round($done / $tasks->count() * 100) : 0;

        return view('onboarding.index', [
            'employee' => $employee,
            'tasks' => $tasks,
            'done' => $done,
            'progress' => $progress,
        ]);
    }

    public function toggle(OnboardingTask $task): RedirectResponse
    {
        $employee = auth()->user()->employee;
        abort_unless($employee && $task->employee_id === $employee->id, 403);

        $task->update([
            'is_done' => ! $task->is_done,
            'completed_at' => $task->is_done ? null : now(),
        ]);

        return back();
    }
}
