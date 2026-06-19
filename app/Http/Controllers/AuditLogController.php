<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class AuditLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:audit-logs.view', only: ['index']),
        ];
    }

    public function index(Request $request): View
    {
        $search = $request->query('q');
        $action = $request->query('action');

        $logs = AuditLog::query()
            ->with('user')
            ->search($search)
            ->when($action, fn ($query) => $query->where('action', $action))
            ->latest()
            ->paginate(config('hrpro.per_page'))
            ->withQueryString();

        // Distinct actions for the filter dropdown (value => Thai label).
        $actionLabels = [
            'created' => 'สร้าง',
            'updated' => 'แก้ไข',
            'deleted' => 'ลบ',
            'approved' => 'อนุมัติ',
            'rejected' => 'ไม่อนุมัติ',
            'login' => 'เข้าสู่ระบบ',
            'logout' => 'ออกจากระบบ',
        ];

        $distinctActions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $actionOptions = $distinctActions->mapWithKeys(fn (string $value) => [
            $value => $actionLabels[$value] ?? $value,
        ])->all();

        return view('audit-logs.index', [
            'logs' => $logs,
            'search' => $search,
            'action' => $action,
            'actionOptions' => $actionOptions,
            'actionLabels' => $actionLabels,
        ]);
    }
}
