<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasLabel, HasColor
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Returned => 'Returned',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::Processing => 'primary',
            self::Shipped => 'info',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
            self::Returned => 'gray',
        };
    }

    /** Tailwind classes for the storefront badge. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Confirmed, self::Shipped => 'bg-sky-100 text-sky-800',
            self::Processing => 'bg-indigo-100 text-indigo-800',
            self::Delivered => 'bg-emerald-100 text-emerald-800',
            self::Cancelled => 'bg-rose-100 text-rose-800',
            self::Returned => 'bg-gray-100 text-gray-700',
        };
    }

    /** Statuses whose stock is released back to inventory. */
    public function releasesStock(): bool
    {
        return in_array($this, [self::Cancelled, self::Returned], true);
    }

    /** The happy-path steps used for the tracking timeline. */
    public static function timeline(): array
    {
        return [self::Pending, self::Confirmed, self::Processing, self::Shipped, self::Delivered];
    }
}
