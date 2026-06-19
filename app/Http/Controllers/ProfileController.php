<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = auth()->user();

        return view('profile.edit', [
            'user' => $user,
            'employee' => $user->employee,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $user->fill($request->validated());
        $user->save();

        AuditLogger::log('updated', 'แก้ไขข้อมูลโปรไฟล์ส่วนตัว', $user);

        return redirect()->route('profile.edit')->with('success', 'บันทึกข้อมูลโปรไฟล์เรียบร้อยแล้ว');
    }

    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $user->update(['password' => $request->validated()['password']]);

        AuditLogger::log('updated', 'เปลี่ยนรหัสผ่าน', $user);

        return redirect()->route('profile.edit')->with('success', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
    }
}
