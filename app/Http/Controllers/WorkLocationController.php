<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkLocationRequest;
use App\Http\Requests\UpdateWorkLocationRequest;
use App\Models\WorkLocation;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Administration of the geofenced areas employees may clock in from.
 */
class WorkLocationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:work-locations.view', only: ['index']),
            new Middleware('permission:work-locations.create', only: ['create', 'store']),
            new Middleware('permission:work-locations.update', only: ['edit', 'update']),
            new Middleware('permission:work-locations.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $locations = WorkLocation::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(config('hrpro.per_page'))
            ->withQueryString();

        return view('work-locations.index', [
            'locations' => $locations,
            'enforced' => (bool) config('hrpro.attendance.require_location', true),
        ]);
    }

    public function create(): View
    {
        return view('work-locations.create');
    }

    public function store(StoreWorkLocationRequest $request): RedirectResponse
    {
        $location = WorkLocation::create($request->validated());

        AuditLogger::log('created', 'เพิ่มบริเวณลงเวลา: '.$location->name, $location);

        return redirect()->route('work-locations.index')->with('success', 'เพิ่มบริเวณลงเวลาเรียบร้อยแล้ว');
    }

    public function edit(WorkLocation $workLocation): View
    {
        return view('work-locations.edit', [
            'location' => $workLocation,
        ]);
    }

    public function update(UpdateWorkLocationRequest $request, WorkLocation $workLocation): RedirectResponse
    {
        $workLocation->update($request->validated());

        AuditLogger::log('updated', 'แก้ไขบริเวณลงเวลา: '.$workLocation->name, $workLocation);

        return redirect()->route('work-locations.index')->with('success', 'บันทึกบริเวณลงเวลาเรียบร้อยแล้ว');
    }

    public function destroy(WorkLocation $workLocation): RedirectResponse
    {
        $name = $workLocation->name;
        $workLocation->delete();

        AuditLogger::log('deleted', 'ลบบริเวณลงเวลา: '.$name, $workLocation);

        return redirect()->route('work-locations.index')->with('success', 'ลบบริเวณลงเวลาเรียบร้อยแล้ว');
    }
}
