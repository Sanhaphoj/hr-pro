<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Live results for the command palette (Ctrl+K). Pages and actions are
 * listed in the page itself; this only answers for records, and only the
 * kinds the person may already open: people for employees.view, published
 * announcements for everyone.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));
        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $user = $request->user();
        $results = [];

        if ($user->hasPermission('employees.view')) {
            Employee::query()
                ->search($term)
                ->with(['department:id,name', 'position:id,title'])
                ->orderBy('first_name')
                ->limit(6)
                ->get()
                ->each(function (Employee $employee) use (&$results) {
                    $results[] = [
                        'group' => 'พนักงาน',
                        'title' => $employee->full_name,
                        'hint' => collect([$employee->employee_code, $employee->position?->title, $employee->department?->name])->filter()->join(' · '),
                        'url' => route('employees.show', $employee),
                        'initials' => $employee->initials,
                        'color' => avatar_color($employee->email),
                    ];
                });
        }

        Announcement::published()
            ->where('title', 'like', "%{$term}%")
            ->latest('published_at')
            ->limit(4)
            ->get(['id', 'title', 'published_at'])
            ->each(function (Announcement $announcement) use (&$results) {
                $results[] = [
                    'group' => 'ประกาศ',
                    'title' => $announcement->title,
                    'hint' => thai_date($announcement->published_at),
                    'url' => route('announcements.show', $announcement),
                    'icon' => 'megaphone',
                ];
            });

        return response()->json(['results' => $results]);
    }
}
