<?php

namespace App\Models;

use App\Models\Concerns\FlushesStorefrontCache;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use FlushesStorefrontCache, HasSlug;

    protected static string $slugFrom = 'title';

    protected $fillable = ['title', 'slug', 'content', 'is_active', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getUrlAttribute(): string
    {
        return route('page.show', $this->slug);
    }

    public function getSeoDescriptionAttribute(): string
    {
        return $this->meta_description ?: Str::limit(trim(strip_tags((string) $this->content)), 158);
    }
}
