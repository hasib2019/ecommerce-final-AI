<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Redirect extends Model
{
    protected $fillable = ['from_path', 'to_url', 'status_code', 'hits', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $r) {
            $r->from_path = '/'.ltrim(parse_url(trim($r->from_path), PHP_URL_PATH) ?: '', '/');
        });

        $flush = fn () => Cache::forget('redirects.map');
        static::saved($flush);
        static::deleted($flush);
    }

    /** @return array<string, array{0:string,1:int,2:int}> path => [to, code, id] */
    public static function map(): array
    {
        return Cache::rememberForever('redirects.map', fn () => static::where('is_active', true)
            ->get(['id', 'from_path', 'to_url', 'status_code'])
            ->mapWithKeys(fn ($r) => [rtrim($r->from_path, '/') ?: '/' => [$r->to_url, $r->status_code, $r->id]])
            ->all());
    }
}
