<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:users.view', only: ['index', 'show']),
            new Middleware('permission:users.create', only: ['create', 'store']),
            new Middleware('permission:users.update', only: ['edit', 'update']),
            new Middleware('permission:users.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(config('hrpro.per_page'))
            ->withQueryString();

        return view('settings.users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('settings.users.create', [
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->roles()->sync($validated['roles'] ?? []);

        AuditLogger::log('created', 'เพิ่มผู้ใช้งาน: ' . $user->name, $user);

        return redirect()->route('settings.users.index')
            ->with('success', 'เพิ่มผู้ใช้งานเรียบร้อยแล้ว');
    }

    public function show(User $user): View
    {
        $user->load('roles.permissions', 'employee');

        return view('settings.users.show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('settings.users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_active = $request->boolean('is_active');

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        $user->roles()->sync($validated['roles'] ?? []);

        AuditLogger::log('updated', 'แก้ไขผู้ใช้งาน: ' . $user->name, $user);

        return redirect()->route('settings.users.index')
            ->with('success', 'บันทึกข้อมูลผู้ใช้งานเรียบร้อยแล้ว');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('settings.users.index')
                ->with('error', 'ไม่สามารถลบบัญชีตนเองได้');
        }

        $name = $user->name;
        $user->roles()->detach();
        $user->delete();

        AuditLogger::log('deleted', 'ลบผู้ใช้งาน: ' . $name, $user);

        return redirect()->route('settings.users.index')
            ->with('success', 'ลบผู้ใช้งานเรียบร้อยแล้ว');
    }
}
