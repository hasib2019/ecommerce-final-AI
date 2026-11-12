<?php

namespace App\Models;

use App\Support\CacheVersion;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    protected static function booted(): void
    {
        $flush = function () {
            Settings::flush();
            CacheVersion::bump();
        };

        static::saved($flush);
        static::deleted($flush);
    }
}
