<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:roles.view', only: ['index', 'show']),
            new Middleware('permission:roles.create', only: ['create', 'store']),
            new Middleware('permission:roles.update', only: ['edit', 'update']),
            new Middleware('permission:roles.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $roles = Role::query()
            ->withCount('permissions', 'users')
            ->orderBy('name')
            ->paginate(config('hrpro.per_page'))
            ->withQueryString();

        return view('settings.roles.index', [
            'roles' => $roles,
        ]);
    }

    public function create(): View
    {
        return view('settings.roles.create', [
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?: Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        AuditLogger::log('created', 'เพิ่มบทบาท: ' . $role->name, $role);

        return redirect()->route('settings.roles.index')
            ->with('success', 'เพิ่มบทบาทเรียบร้อยแล้ว');
    }

    public function show(Role $role): View
    {
        $role->load('permissions', 'users');

        return view('settings.roles.show', [
            'role' => $role,
            'permissionGroups' => $this->permissionGroups(),
            'rolePermissionIds' => $role->permissions->pluck('id')->all(),
        ]);
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        return view('settings.roles.edit', [
            'role' => $role,
            'permissionGroups' => $this->permissionGroups(),
            'rolePermissionIds' => $role->permissions->pluck('id')->all(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        $role->name = $validated['name'];
        $role->description = $validated['description'] ?? null;

        // System roles keep their original slug; it must not be changed.
        if (! $role->is_system) {
            $role->slug = $validated['slug'] ?: Str::slug($validated['name']);
        }

        $role->save();

        $role->syncPermissions($validated['permissions'] ?? []);

        AuditLogger::log('updated', 'แก้ไขบทบาท: ' . $role->name, $role);

        return redirect()->route('settings.roles.index')
            ->with('success', 'บันทึกข้อมูลบทบาทเรียบร้อยแล้ว');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return redirect()->route('settings.roles.index')
                ->with('error', 'ไม่สามารถลบบทบาทของระบบ');
        }

        $name = $role->name;
        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        AuditLogger::log('deleted', 'ลบบทบาท: ' . $name, $role);

        return redirect()->route('settings.roles.index')
            ->with('success', 'ลบบทบาทเรียบร้อยแล้ว');
    }

    /**
     * All permissions grouped by their `group` column, ordered for display.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, \App\Models\Permission>>
     */
    private function permissionGroups()
    {
        return Permission::orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group');
    }
}
