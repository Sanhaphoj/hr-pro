<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Support\Collection;

class OrgChartService
{
    /**
     * Build the organisation chart as a nested department tree.
     *
     * Departments are linked by `parent_id` (parent/children) and each carries
     * its head (manager Employee) and headcount. The whole structure hangs under
     * a synthetic company root. Read-only; no schema changes required.
     *
     * @return array{company: string, roots: Collection, department_count: int, employee_count: int, position_count: int, managed_count: int}
     */
    public function tree(): array
    {
        $departments = Department::query()
            ->with('manager.position')
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        // Group by parent (roots keyed under 0) and stitch the tree in memory so
        // headcount/manager stay attached at every depth without N+1 queries.
        $grouped = $departments->groupBy(fn (Department $d) => $d->parent_id ?? 0);

        $build = function ($parentKey) use (&$build, $grouped): Collection {
            return $grouped->get($parentKey, collect())
                ->map(function (Department $dept) use (&$build) {
                    $dept->setRelation('subtree', $build($dept->id));

                    return $dept;
                })
                ->values();
        };

        return [
            'company' => config('hrpro.company_name'),
            'roots' => $build(0),
            'department_count' => $departments->count(),
            'employee_count' => Employee::count(),
            'position_count' => Position::count(),
            'managed_count' => $departments->whereNotNull('manager_id')->count(),
        ];
    }
}
