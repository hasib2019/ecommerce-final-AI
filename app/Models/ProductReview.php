<?php

namespace App\Models;

use App\Support\CacheVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'name', 'email', 'rating', 'comment', 'is_verified', 'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_approved' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $refresh = function (ProductReview $review) {
            Product::withTrashed()->find($review->product_id)?->recalculateRating();
            CacheVersion::bump();
        };

        static::saved($refresh);
        static::deleted($refresh);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
