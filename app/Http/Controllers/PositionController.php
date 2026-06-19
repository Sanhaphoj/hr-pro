<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Models\Department;
use App\Models\Position;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PositionController extends Controller implements HasMiddleware
{
    /**
     * Thai labels for each position level.
     *
     * @var array<string, string>
     */
    public const LEVEL_LABELS = [
        'junior' => 'ระดับต้น',
        'mid' => 'ระดับกลาง',
        'senior' => 'อาวุโส',
        'lead' => 'หัวหน้าทีม',
        'manager' => 'ผู้จัดการ',
        'executive' => 'ผู้บริหาร',
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:positions.view', only: ['index']),
            new Middleware('permission:positions.create', only: ['create', 'store']),
            new Middleware('permission:positions.update', only: ['edit', 'update']),
            new Middleware('permission:positions.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = $request->query('search');

        $positions = Position::query()
            ->with('department')
            ->search($search)
            ->orderBy('title')
            ->paginate(config('hrpro.per_page'))
            ->withQueryString();

        return view('positions.index', [
            'positions' => $positions,
            'search' => $search,
            'levelLabels' => self::LEVEL_LABELS,
        ]);
    }

    public function create(): View
    {
        return view('positions.create', [
            'departments' => $this->departmentOptions(),
            'levels' => self::LEVEL_LABELS,
        ]);
    }

    public function store(StorePositionRequest $request): RedirectResponse
    {
        $position = Position::create($request->validated());

        AuditLogger::log('created', "เพิ่มตำแหน่ง: {$position->title}", $position);

        return redirect()->route('positions.index')->with('success', 'เพิ่มตำแหน่งเรียบร้อยแล้ว');
    }

    public function edit(Position $position): View
    {
        return view('positions.edit', [
            'position' => $position,
            'departments' => $this->departmentOptions(),
            'levels' => self::LEVEL_LABELS,
        ]);
    }

    public function update(UpdatePositionRequest $request, Position $position): RedirectResponse
    {
        $position->update($request->validated());

        AuditLogger::log('updated', "แก้ไขตำแหน่ง: {$position->title}", $position);

        return redirect()->route('positions.index')->with('success', 'บันทึกข้อมูลตำแหน่งเรียบร้อยแล้ว');
    }

    public function destroy(Position $position): RedirectResponse
    {
        $title = $position->title;
        $position->delete();

        AuditLogger::log('deleted', "ลบตำแหน่ง: {$title}", $position);

        return redirect()->route('positions.index')->with('success', 'ลบตำแหน่งเรียบร้อยแล้ว');
    }

    /**
     * Options for the department select keyed by id with the name as label.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function departmentOptions()
    {
        return Department::query()->orderBy('name')->pluck('name', 'id');
    }
}
