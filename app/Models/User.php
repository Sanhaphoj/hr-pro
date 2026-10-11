<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
        'theme',
        'color_scheme',
    ];

    /** Accent themes offered on the appearance screen. */
    public const THEMES = ['teal', 'indigo', 'navy', 'emerald', 'plum'];

    /** Light, dark, or follow the operating system. */
    public const COLOR_SCHEMES = ['light', 'dark', 'auto'];

    /**
     * Mirrors the column defaults so a freshly built model already knows its
     * appearance — the database default only lands after a round trip.
     */
    protected $attributes = [
        'theme' => 'teal',
        'color_scheme' => 'light',
    ];

    /**
     * A new account starts on the default look from Settings → System rather
     * than the hard-wired teal.
     */
    public function __construct(array $attributes = [])
    {
        $theme = config('hrpro.appearance.theme', 'teal');
        $scheme = config('hrpro.appearance.color_scheme', 'light');

        $this->attributes['theme'] = in_array($theme, self::THEMES, true) ? $theme : 'teal';
        $this->attributes['color_scheme'] = in_array($scheme, self::COLOR_SCHEMES, true) ? $scheme : 'light';

        parent::__construct($attributes);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    // ----- Relationships ----------------------------------------------------

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ----- Authorization ----------------------------------------------------

    public function isSuperAdmin(): bool
    {
        return $this->roles->contains(fn (Role $role) => $role->slug === 'super-admin');
    }

    /**
     * Flat list of every permission name granted through the user's roles.
     */
    /**
     * Whether this account must take a front-camera photo to punch in or out.
     *
     * Read from the permissions actually attached to its roles rather than
     * through hasPermission(), which waves a super-admin through everything —
     * for a requirement that would mean "always forced" instead of "allowed".
     *
     * @param  'in'|'out'  $action
     */
    public function requiresSelfie(string $action): bool
    {
        return in_array('attendance.selfie-'.$action, $this->permissionNames(), true);
    }

    public function permissionNames(): array
    {
        $this->loadMissing('roles.permissions');

        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissionNames(), true);
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains(fn (Role $role) => $role->slug === $slug);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function assignRole(Role|int $role): void
    {
        $this->roles()->syncWithoutDetaching([$role instanceof Role ? $role->id : $role]);
    }

    // ----- Accessors --------------------------------------------------------

    public function getInitialsAttribute(): string
    {
        return initials($this->name);
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->whereNull('read_at')->count();
    }
}
