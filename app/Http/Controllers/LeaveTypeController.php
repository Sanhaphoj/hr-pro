<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Models\LeaveType;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LeaveTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:leave-types.view', only: ['index']),
            new Middleware('permission:leave-types.create', only: ['create', 'store']),
            new Middleware('permission:leave-types.update', only: ['edit', 'update']),
            new Middleware('permission:leave-types.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $leaveTypes = LeaveType::query()
            ->orderBy('name')
            ->paginate(config('hrpro.per_page'))
            ->withQueryString();

        return view('leave-types.index', [
            'leaveTypes' => $leaveTypes,
        ]);
    }

    public function create(): View
    {
        return view('leave-types.create', [
            'colors' => $this->colorOptions(),
        ]);
    }

    public function store(StoreLeaveTypeRequest $request): RedirectResponse
    {
        $leaveType = LeaveType::create($request->validated());

        AuditLogger::log('created', 'เพิ่มประเภทการลา: '.$leaveType->name, $leaveType);

        return redirect()->route('leave-types.index')->with('success', 'เพิ่มประเภทการลาเรียบร้อยแล้ว');
    }

    public function edit(LeaveType $leaveType): View
    {
        return view('leave-types.edit', [
            'leaveType' => $leaveType,
            'colors' => $this->colorOptions(),
        ]);
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $leaveType->update($request->validated());

        AuditLogger::log('updated', 'แก้ไขประเภทการลา: '.$leaveType->name, $leaveType);

        return redirect()->route('leave-types.index')->with('success', 'บันทึกประเภทการลาเรียบร้อยแล้ว');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $name = $leaveType->name;
        $leaveType->delete();

        AuditLogger::log('deleted', 'ลบประเภทการลา: '.$name, $leaveType);

        return redirect()->route('leave-types.index')->with('success', 'ลบประเภทการลาเรียบร้อยแล้ว');
    }

    /**
     * Color options for the leave type form (value => Thai label).
     *
     * @return array<string, string>
     */
    private function colorOptions(): array
    {
        return [
            'blue' => 'น้ำเงิน',
            'green' => 'เขียว',
            'amber' => 'เหลือง',
            'red' => 'แดง',
            'gray' => 'เทา',
        ];
    }
}
