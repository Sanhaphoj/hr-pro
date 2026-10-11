<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Support\SettingsCatalogue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Stores what is changed on the settings screen and lays it over config().
 *
 * config/ and .env remain the defaults. A stored row wins over them; removing
 * the row ("restore defaults") brings the file value back. Secrets are kept
 * encrypted with the application key.
 */
class SettingsService
{
    private const CACHE_KEY = 'hrpro.settings.v1';

    /** Config values as they were before any stored setting was applied. */
    private array $defaults = [];

    /**
     * Push the effective value of every setting into config().
     */
    public function apply(): void
    {
        $stored = $this->stored();

        foreach (SettingsCatalogue::fields() as $key => $field) {
            $paths = (array) $field['config'];

            if (! array_key_exists($key, $this->defaults)) {
                $this->defaults[$key] = config($paths[0]);
            }

            $value = array_key_exists($key, $stored)
                ? $this->fromStorage($field, $stored[$key])
                : $this->defaults[$key];

            foreach ($paths as $path) {
                config()->set($path, $value);
            }
        }
    }

    /** The value in force for "group.field". */
    public function effective(string $key): mixed
    {
        $field = SettingsCatalogue::fields()[$key] ?? null;

        return $field ? config(((array) $field['config'])[0]) : null;
    }

    /** Whether "group.field" differs from the file default. */
    public function isCustomised(string $key): bool
    {
        return array_key_exists($key, $this->stored());
    }

    public function customisedCount(string $group): int
    {
        return count(array_filter(
            array_keys($this->stored()),
            fn (string $key) => str_starts_with($key, $group.'.'),
        ));
    }

    public function lastChange(string $group): ?Setting
    {
        return Setting::query()
            ->with('updater:id,name')
            ->where('key', 'like', $group.'.%')
            ->latest('updated_at')
            ->first();
    }

    /**
     * Save one group's values. A value equal to the default removes the row,
     * so "customised" always means "differs from the file".
     *
     * @param  array<string, mixed>  $values  field name => value, already cast
     * @return array<string, array{0: mixed, 1: mixed}> "group.field" => [before, after] for what changed
     */
    public function save(string $group, array $values, ?User $by): array
    {
        $fields = SettingsCatalogue::groupFields($group);
        $changes = [];

        foreach ($values as $name => $value) {
            if (! isset($fields[$name])) {
                continue;
            }

            $field = $fields[$name];
            $key = $group.'.'.$name;
            $secret = (bool) ($field['secret'] ?? false);
            $before = $this->effective($key);

            if (! $secret && $this->same($before, $value)) {
                continue;
            }

            $backToDefault = $secret ? $value === null : $this->same($value, $this->defaults[$key] ?? null);

            if ($backToDefault) {
                Setting::where('key', $key)->delete();
            } else {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $this->toStorage($field, $value), 'updated_by' => $by?->id],
                );
            }

            $changes[$key] = [$before, $value];
        }

        $this->refresh();

        return $changes;
    }

    /**
     * Drop every stored value of a group.
     *
     * @return array<int, string> keys that had been customised
     */
    public function reset(string $group): array
    {
        $keys = array_map(fn (string $name) => $group.'.'.$name, array_keys(SettingsCatalogue::groupFields($group)));
        $removed = Setting::whereIn('key', $keys)->pluck('key')->all();

        Setting::whereIn('key', $keys)->delete();
        $this->refresh();

        return $removed;
    }

    private function refresh(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->apply();
    }

    /** @return array<string, mixed> */
    private function stored(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->pluck('value', 'key')->all());
        } catch (\Throwable) {
            // Before the settings table exists (fresh install, first migrate)
            // there is nothing to apply.
            return [];
        }
    }

    private function toStorage(array $field, mixed $value): mixed
    {
        if (($field['secret'] ?? false) && $value !== null) {
            return Crypt::encryptString((string) $value);
        }

        return $value;
    }

    private function fromStorage(array $field, mixed $value): mixed
    {
        if (($field['secret'] ?? false) && is_string($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                // Encrypted under a different APP_KEY: unusable, treat as unset.
                return null;
            }
        }

        return $value;
    }

    /**
     * Equality the way a form sees it: "15" equals 15 and [1, 2] equals
     * ["1", "2"], but null is not 0 and an empty value is not "off".
     */
    private function same(mixed $a, mixed $b): bool
    {
        if (is_array($a) || is_array($b)) {
            return array_values((array) $a) == array_values((array) $b);
        }

        if ($a === null || $b === null) {
            return $a === $b;
        }

        if (is_bool($a) || is_bool($b)) {
            return (bool) $a === (bool) $b;
        }

        return (string) $a === (string) $b;
    }
}
