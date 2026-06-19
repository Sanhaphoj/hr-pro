<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class AnnouncementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:announcements.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $canManage = $request->user()->hasPermission('announcements.manage');

        $query = Announcement::query()->with('author');

        // Non-managers only ever see published announcements.
        if (! $canManage) {
            $query->published();
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $announcements = $query->visibleOrder()
            ->paginate(config('hrpro.per_page'))
            ->withQueryString();

        $categories = [
            'general' => 'ทั่วไป',
            'policy' => 'นโยบาย',
            'event' => 'กิจกรรม',
            'urgent' => 'ด่วน',
        ];

        return view('announcements.index', [
            'announcements' => $announcements,
            'categories' => $categories,
            'category' => $category,
            'canManage' => $canManage,
        ]);
    }

    public function create(): View
    {
        return view('announcements.create', [
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['author_id'] = $request->user()->id;

        if (! empty($data['is_published']) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $announcement = Announcement::create($data);

        AuditLogger::log('created', 'สร้างประกาศ: '.$announcement->title, $announcement);

        return redirect()->route('announcements.index')->with('success', 'สร้างประกาศเรียบร้อยแล้ว');
    }

    public function show(Request $request, Announcement $announcement): View
    {
        // Drafts (unpublished) are only visible to users who can manage announcements.
        abort_unless(
            $announcement->is_published || $request->user()->hasPermission('announcements.manage'),
            403,
        );

        $announcement->load('author');

        return view('announcements.show', [
            'announcement' => $announcement,
        ]);
    }

    public function edit(Announcement $announcement): View
    {
        return view('announcements.edit', [
            'announcement' => $announcement,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['is_published']) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $announcement->update($data);

        AuditLogger::log('updated', 'แก้ไขประกาศ: '.$announcement->title, $announcement);

        return redirect()->route('announcements.index')->with('success', 'บันทึกการแก้ไขประกาศเรียบร้อยแล้ว');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $title = $announcement->title;

        $announcement->delete();

        AuditLogger::log('deleted', 'ลบประกาศ: '.$title, $announcement);

        return redirect()->route('announcements.index')->with('success', 'ลบประกาศเรียบร้อยแล้ว');
    }

    /**
     * Thai-labelled category options for select inputs.
     *
     * @return array<string,string>
     */
    private function categoryOptions(): array
    {
        return [
            'general' => 'ทั่วไป',
            'policy' => 'นโยบาย',
            'event' => 'กิจกรรม',
            'urgent' => 'ด่วน',
        ];
    }
}
