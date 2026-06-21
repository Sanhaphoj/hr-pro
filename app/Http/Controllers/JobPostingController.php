<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\JobPosting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class JobPostingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:recruitment.view', only: ['index', 'show']),
            new Middleware('permission:recruitment.manage', only: ['create', 'store', 'destroy', 'storeCandidate', 'updateCandidate']),
        ];
    }

    public function index(): View
    {
        $postings = JobPosting::query()
            ->with('department:id,name')
            ->withCount('candidates')
            ->latest()
            ->paginate(config('hrpro.per_page'));

        return view('recruitment.index', ['postings' => $postings]);
    }

    public function create(): View
    {
        return view('recruitment.create', [
            'departments' => Department::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'openings' => ['required', 'integer', 'min:1', 'max:999'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,intern'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['status'] = 'open';
        $data['posted_at'] = now();
        $posting = JobPosting::create($data);

        AuditLogger::log('created', "เปิดรับสมัคร: {$posting->title}", $posting);

        return redirect()->route('recruitment.show', $posting)->with('success', 'สร้างประกาศรับสมัครเรียบร้อย');
    }

    public function show(JobPosting $recruitment): View
    {
        $recruitment->load(['department', 'candidates' => fn ($q) => $q->latest()]);

        return view('recruitment.show', [
            'posting' => $recruitment,
            'stages' => Candidate::STAGE_LABELS,
        ]);
    }

    public function destroy(JobPosting $recruitment): RedirectResponse
    {
        $title = $recruitment->title;
        $recruitment->delete();

        AuditLogger::log('deleted', "ลบประกาศรับสมัคร: {$title}", $recruitment);

        return redirect()->route('recruitment.index')->with('success', 'ลบประกาศรับสมัครเรียบร้อย');
    }

    public function storeCandidate(Request $request, JobPosting $recruitment): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $recruitment->candidates()->create($data + ['stage' => 'applied']);

        return back()->with('success', 'เพิ่มผู้สมัครเรียบร้อย');
    }

    public function updateCandidate(Request $request, JobPosting $recruitment, Candidate $candidate): RedirectResponse
    {
        abort_unless($candidate->job_posting_id === $recruitment->id, 404);

        $data = $request->validate([
            'stage' => ['required', 'in:'.implode(',', Candidate::STAGES)],
        ]);

        $candidate->update($data);

        return back()->with('success', 'อัปเดตสถานะผู้สมัครเรียบร้อย');
    }
}
