<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * A single "generation" number for storefront caches.
 *
 * Every cached storefront fragment includes this number in its key, so bumping it
 * invalidates everything at once without needing cache tags (works with any driver).
 */
class CacheVersion
{
    private const KEY = 'storefront.cache_version';

    private static ?int $memo = null;

    public static function get(): int
    {
        return self::$memo ??= (int) Cache::rememberForever(self::KEY, fn () => time());
    }

    public static function bump(): void
    {
        self::$memo = time() + random_int(1, 999);
        Cache::forever(self::KEY, self::$memo);
    }

    /** Cache a value under a versioned key. */
    public static function remember(string $key, int $seconds, \Closure $callback): mixed
    {
        return Cache::remember('sf.'.self::get().'.'.$key, $seconds, $callback);
    }
}
