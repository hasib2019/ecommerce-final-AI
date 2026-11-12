<?php

namespace App\Services;

use App\Models\User;

/** Session wishlist for guests, database wishlist for signed-in customers. */
class Wishlist
{
    private const KEY = 'wishlist';

    private ?array $ids = null;

    public function ids(): array
    {
        if ($this->ids !== null) {
            return $this->ids;
        }

        $user = auth()->user();

        return $this->ids = $user
            ? $user->wishlist()->pluck('products.id')->map(fn ($id) => (int) $id)->all()
            : array_map('intval', (array) session(self::KEY, []));
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    public function count(): int
    {
        return count($this->ids());
    }

    /** @return bool true if the product is now in the wishlist */
    public function toggle(int $productId): bool
    {
        $user = auth()->user();
        $added = ! $this->has($productId);

        if ($user) {
            $added ? $user->wishlist()->syncWithoutDetaching([$productId]) : $user->wishlist()->detach($productId);
        } else {
            $ids = $this->ids();
            $ids = $added ? array_values(array_unique([...$ids, $productId])) : array_values(array_diff($ids, [$productId]));
            session([self::KEY => $ids]);
        }

        $this->ids = null;

        return $added;
    }

    public function mergeInto(User $user): void
    {
        $ids = array_map('intval', (array) session(self::KEY, []));
        if ($ids) {
            $user->wishlist()->syncWithoutDetaching($ids);
            session()->forget(self::KEY);
        }
        $this->ids = null;
    }
}
