<?php

namespace App\Models;

use App\Models\Concerns\FlushesStorefrontCache;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Post extends Model
{
    use FlushesStorefrontCache, HasSlug;

    protected static string $slugFrom = 'title';

    protected $fillable = [
        'user_id', 'title', 'slug', 'excerpt', 'content', 'image', 'is_published', 'published_at',
        'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function getUrlAttribute(): string
    {
        return route('blog.show', $this->slug);
    }

    public function getSeoDescriptionAttribute(): string
    {
        return $this->meta_description ?: Str::limit(trim(strip_tags($this->excerpt ?: (string) $this->content)), 158);
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->content)) / 200));
    }
}
