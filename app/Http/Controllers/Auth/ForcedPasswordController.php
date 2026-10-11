<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForcePasswordChangeRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The screen an account on a handed-out password lands on: the first
 * production administrator, or anyone reset with `hrpro:admin-password`.
 */
class ForcedPasswordController extends Controller
{
    public function edit(): View|RedirectResponse
    {
        if (! auth()->user()->must_change_password) {
            return redirect()->route('time-clock.show');
        }

        return view('auth.force-password', ['user' => auth()->user()]);
    }

    public function update(ForcePasswordChangeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill([
            'password' => $request->validated()['password'],
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        AuditLogger::log('updated', 'ตั้งรหัสผ่านใหม่แทนรหัสผ่านชั่วคราว', $user);

        return redirect()->route('time-clock.show')
            ->with('success', 'ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว ใช้รหัสผ่านนี้ในการเข้าสู่ระบบครั้งต่อไป');
    }
}
