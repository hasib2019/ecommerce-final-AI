<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Settings are stored one row per group (general, header, footer, …) as JSON.
 * Reads are served from a single cached array merged over config('shop.defaults').
 */
class Settings
{
    private const CACHE_KEY = 'settings.all';

    private static ?array $resolved = null;

    public static function all(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $stored = [];
        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->pluck('value', 'key')->all());
        } catch (Throwable) {
            // Database not migrated yet – fall back to defaults.
        }

        $merged = config('shop.defaults', []);
        foreach ($stored as $group => $values) {
            if (is_array($values)) {
                // Shallow merge per group: list-type values (menus, columns) are replaced, not merged.
                $merged[$group] = array_merge($merged[$group] ?? [], $values);
            }
        }

        return self::$resolved = $merged;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = data_get(self::all(), $key);

        return $value ?? $default;
    }

    public static function group(string $group): array
    {
        return self::all()[$group] ?? [];
    }

    public static function save(string $group, array $values): void
    {
        Setting::query()->updateOrCreate(['key' => $group], ['value' => $values]);
        self::flush();
    }

    public static function flush(): void
    {
        self::$resolved = null;
        Cache::forget(self::CACHE_KEY);
    }
}
