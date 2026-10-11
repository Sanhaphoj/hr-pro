<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * How the first sign-in works.
 *
 * A local install and the public demo sign in with the well-known password
 * and say so on the login screen. A real production install (APP_ENV=production
 * without SEED_DEMO) never does: its administrator gets a random one-time
 * password, printed once by the seeder, that must be replaced at first sign-in.
 */
final class DefaultLogin
{
    /** The password every demo account is seeded with. */
    public const PASSWORD = 'password';

    /** Demo accounts DemoDataSeeder creates, shown on the login screen when they exist. */
    private const DEMO_EMAILS = [
        'hr@hrpro.local',
        'manager@hrpro.local',
        'employee@hrpro.local',
    ];

    /** Whether this install hands out the well-known password at all. */
    public static function enabled(): bool
    {
        return ! app()->isProduction() || config('hrpro.demo');
    }

    /** The administrator's first password on a local or demo install. */
    public static function adminPassword(): string
    {
        return config('hrpro.admin.password') ?: self::PASSWORD;
    }

    /**
     * Whether a configured admin password is fit for production: set, and
     * not the well-known one copied over from .env.example.
     */
    public static function isUsableInProduction(?string $password): bool
    {
        return filled($password) && $password !== self::PASSWORD;
    }

    /** A one-time password: letters and digits, so it satisfies the default policy and copies cleanly. */
    public static function generate(): string
    {
        return Str::password(16, symbols: false);
    }

    /**
     * Accounts that still sign in with their seeded password, for the hint on
     * the login screen. Empty on a real production install, and an account
     * drops off as soon as its owner changes the password.
     *
     * @return list<array{role: string, email: string, password: string}>
     */
    public static function accounts(): array
    {
        if (! self::enabled()) {
            return [];
        }

        $candidates = [config('hrpro.admin.email') => self::adminPassword()]
            + array_fill_keys(self::DEMO_EMAILS, self::PASSWORD);

        return User::query()
            ->with('roles:id,name')
            ->whereIn('email', array_keys($candidates))
            ->where('is_active', true)
            ->get()
            ->sortBy(fn (User $user) => array_search($user->email, array_keys($candidates), true))
            ->filter(fn (User $user) => self::stillUses($user, $candidates[$user->email]))
            ->map(fn (User $user) => [
                'role' => $user->roles->first()?->name ?? $user->name,
                'email' => $user->email,
                'password' => $candidates[$user->email],
            ])
            ->values()
            ->all();
    }

    /**
     * Bcrypt is slow on purpose, so the answer is remembered against the
     * stored hash — a new password means a new hash and a fresh check.
     */
    private static function stillUses(User $user, string $password): bool
    {
        return Cache::rememberForever(
            'default-login:'.sha1($user->password.'|'.$password),
            fn () => Hash::check($password, $user->password),
        );
    }
}
