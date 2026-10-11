<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogger;
use App\Support\DefaultLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        // Local and demo installs list the accounts that still sign in with
        // the seeded password; a production install lists none.
        return view('auth.login', ['defaultLogins' => DefaultLogin::accounts()]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        $user->forceFill(['last_login_at' => now()])->save();

        AuditLogger::log('login', "เข้าสู่ระบบ ({$user->email})");

        // Clocking in is the first thing most people open the system to do,
        // so that is the landing screen; `intended` still wins when the user
        // was bounced here from somewhere else.
        return redirect()->intended(route('time-clock.show'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        AuditLogger::log('logout', 'ออกจากระบบ');

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'คุณออกจากระบบเรียบร้อยแล้ว');
    }
}
