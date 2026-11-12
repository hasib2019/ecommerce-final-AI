<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'customer_name', 'customer_phone', 'customer_email', 'shipping_address',
        'city', 'area', 'note', 'subtotal', 'discount', 'shipping_cost', 'total', 'coupon_code',
        'shipping_method', 'payment_method', 'payment_status', 'transaction_id', 'status', 'admin_note', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->order_number ??= 'TMP-'.Str::upper(Str::random(12));
        });

        static::created(function (Order $order) {
            $prefix = Str::upper((string) setting('checkout.order_prefix', 'ORD'));
            $order->order_number = $prefix.str_pad((string) (100000 + $order->id), 6, '0', STR_PAD_LEFT);
            $order->saveQuietly();
            $order->histories()->create(['status' => $order->status->value, 'note' => 'Order placed']);
        });

        static::updated(function (Order $order) {
            if (! $order->wasChanged('status')) {
                return;
            }

            $raw = $order->getOriginal('status');
            $from = $raw instanceof OrderStatus ? $raw : OrderStatus::tryFrom((string) $raw);
            $to = $order->status;

            $order->histories()->create(['status' => $to->value]);

            // Keep inventory honest when an order is cancelled / returned, or revived.
            $wasReleased = $from?->releasesStock() ?? false;
            if ($to->releasesStock() && ! $wasReleased) {
                $order->adjustStock(+1);
            } elseif (! $to->releasesStock() && $wasReleased) {
                $order->adjustStock(-1);
            }

            if ($to === OrderStatus::Delivered && $order->payment_method === 'cod'
                && $order->payment_status !== PaymentStatus::Paid) {
                $order->updateQuietly(['payment_status' => PaymentStatus::Paid]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    /** +1 returns items to stock, -1 takes them out again. */
    public function adjustStock(int $direction): void
    {
        foreach ($this->items()->with(['product', 'variant'])->get() as $item) {
            $qty = $item->quantity * $direction;

            if ($item->variant) {
                $item->variant->increment('stock', $qty);
                $item->product?->syncStockFromVariants();
            } elseif ($item->product && $item->product->track_stock) {
                $item->product->increment('stock', $qty);
            }

            if ($item->product) {
                $direction > 0
                    ? $item->product->decrement('sold_count', min($item->product->sold_count, $item->quantity))
                    : $item->product->increment('sold_count', $item->quantity);
            }
        }
    }

    public function paymentLabel(): string
    {
        return match ($this->payment_method) {
            'cod' => 'Cash on Delivery',
            'bkash' => 'bKash',
            'nagad' => 'Nagad',
            'bank' => 'Bank Transfer',
            default => Str::headline($this->payment_method),
        };
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }
}
