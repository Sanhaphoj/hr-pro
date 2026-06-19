<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /*
         | Single source of truth for authorization.
         |  - Super Admins bypass every check.
         |  - Any ability written as a permission name (e.g. "employees.view")
         |    is resolved against the user's role-based permissions, so Blade
         |    @can('employees.view') and Gate::authorize() work without having
         |    to register a closure per permission.
         */
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            if (str_contains($ability, '.')) {
                return $user->hasPermission($ability);
            }

            return null; // defer to any explicitly defined gates/policies
        });
    }
}
