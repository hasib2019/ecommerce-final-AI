<?php

namespace App\Models\Concerns;

use App\Support\CacheVersion;

/** Any change to a model using this trait invalidates the storefront caches. */
trait FlushesStorefrontCache
{
    protected static function bootFlushesStorefrontCache(): void
    {
        foreach (['saved', 'deleted', 'restored'] as $event) {
            if ($event === 'restored' && ! method_exists(static::class, 'bootSoftDeletes')) {
                continue;
            }
            static::$event(fn () => CacheVersion::bump());
        }
    }
}
