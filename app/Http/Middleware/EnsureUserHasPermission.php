<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Usage in routes: ->middleware('permission:employees.view')
     * Accepts multiple permissions (pipe-separated) — the user needs any one.
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();

        abort_if(! $user, 401);

        $required = explode('|', $permissions);

        abort_unless($user->hasAnyPermission($required), 403, 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้');

        return $next($request);
    }
}
