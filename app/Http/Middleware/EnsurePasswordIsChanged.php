<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Hold an account that is still on a handed-out password at the
     * "choose your own password" screen until it has done so.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            abort_if($request->expectsJson(), 403, 'กรุณาตั้งรหัสผ่านใหม่ก่อนใช้งาน');

            return redirect()->route('password.force');
        }

        return $next($request);
    }
}
