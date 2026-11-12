<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            $source = $model->{static::slugSource()} ?? '';
            if (blank($model->slug)) {
                $model->slug = $source;
            }
            $model->slug = static::uniqueSlug(Str::slug($model->slug) ?: Str::random(8), $model->getKey());
        });
    }

    protected static function slugSource(): string
    {
        return property_exists(static::class, 'slugFrom') ? static::$slugFrom : 'name';
    }

    public static function uniqueSlug(string $slug, $ignoreId = null): string
    {
        $original = $slug;
        $i = 2;
        $query = fn ($s) => static::query()
            ->when(method_exists(static::class, 'bootSoftDeletes'), fn ($q) => $q->withTrashed())
            ->where('slug', $s)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        while ($query($slug)) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
