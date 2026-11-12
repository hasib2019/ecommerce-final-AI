<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use Illuminate\Support\Collection;

/**
 * Session cart. Only ids + quantities live in the session; prices and stock are
 * always re-read from the database so the customer can never tamper with them.
 */
class Cart
{
    private const ITEMS = 'cart.items';

    private const COUPON = 'cart.coupon';

    private const SHIPPING = 'cart.shipping';

    /** @var Collection<int, CartLine>|null */
    private ?Collection $lines = null;

    public static function keyFor(int $productId, ?int $variantId): string
    {
        return $productId.'-'.($variantId ?: 0);
    }

    public function raw(): array
    {
        return (array) session(self::ITEMS, []);
    }

    /** @return string|null error message */
    public function add(Product $product, ?ProductVariant $variant, int $qty = 1): ?string
    {
        if (! $product->is_active) {
            return 'This product is no longer available.';
        }

        if ($product->has_variants && ! $variant) {
            return 'Please select an option first.';
        }

        if ($variant && (! $variant->is_active || $variant->product_id !== $product->id)) {
            return 'This option is not available.';
        }

        $key = self::keyFor($product->id, $variant?->id);
        $items = $this->raw();
        $current = $items[$key]['q'] ?? 0;
        $wanted = $current + max(1, $qty);

        $line = new CartLine($key, $product, $variant, $wanted);
        $max = $line->maxQuantity();

        if ($max <= 0) {
            return 'Sorry, this item is out of stock.';
        }

        $error = null;
        if ($wanted > $max) {
            $wanted = $max;
            $error = $current >= $max ? "Only {$max} available – you already have them all in your cart." : null;
            if ($error) {
                return $error;
            }
        }

        $items[$key] = ['p' => $product->id, 'v' => $variant?->id, 'q' => $wanted];
        $this->put($items);

        return null;
    }

    public function setQuantity(string $key, int $qty): void
    {
        $items = $this->raw();
        if (! isset($items[$key])) {
            return;
        }

        if ($qty <= 0) {
            unset($items[$key]);
        } else {
            $items[$key]['q'] = min($qty, 99);
        }

        $this->put($items);
    }

    public function remove(string $key): void
    {
        $this->setQuantity($key, 0);
    }

    public function clear(): void
    {
        session()->forget([self::ITEMS, self::COUPON]);
        $this->lines = null;
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /** Fast – reads only the session. */
    public function count(): int
    {
        return (int) array_sum(array_column($this->raw(), 'q'));
    }

    /** @return Collection<int, CartLine> */
    public function lines(): Collection
    {
        if ($this->lines !== null) {
            return $this->lines;
        }

        $items = $this->raw();
        if (! $items) {
            return $this->lines = collect();
        }

        $products = Product::query()->active()
            ->whereIn('id', array_column($items, 'p'))
            ->get()->keyBy('id');

        $variantIds = array_filter(array_column($items, 'v'));
        $variants = $variantIds
            ? ProductVariant::query()->whereIn('id', $variantIds)->where('is_active', true)->get()->keyBy('id')
            : collect();

        $lines = collect();
        $dirty = false;

        foreach ($items as $key => $item) {
            $product = $products->get($item['p']);
            $variant = $item['v'] ? $variants->get($item['v']) : null;

            // Drop lines whose product/variant vanished.
            if (! $product || ($item['v'] && ! $variant) || ($product->has_variants && ! $variant)) {
                unset($items[$key]);
                $dirty = true;

                continue;
            }

            $line = new CartLine($key, $product, $variant, (int) $item['q']);
            $max = $line->maxQuantity();

            if ($max <= 0) {
                unset($items[$key]);
                $dirty = true;

                continue;
            }

            if ($line->quantity > $max) {
                $line->quantity = $max;
                $items[$key]['q'] = $max;
                $dirty = true;
            }

            $lines->push($line);
        }

        if ($dirty) {
            session([self::ITEMS => $items]);
        }

        return $this->lines = $lines;
    }

    public function subtotal(): float
    {
        return round($this->lines()->sum(fn (CartLine $l) => $l->total()), 2);
    }

    // ------------------------------------------------------------------ coupon

    public function coupon(): ?Coupon
    {
        $code = session(self::COUPON);
        if (! $code) {
            return null;
        }

        $coupon = Coupon::findByCode($code);
        if (! $coupon || $coupon->rejectionReason($this->subtotal())) {
            return null;
        }

        return $coupon;
    }

    /** @return string|null error */
    public function applyCoupon(string $code): ?string
    {
        $coupon = Coupon::findByCode($code);
        if (! $coupon) {
            return 'Invalid coupon code.';
        }

        if ($reason = $coupon->rejectionReason($this->subtotal())) {
            return $reason;
        }

        session([self::COUPON => $coupon->code]);

        return null;
    }

    public function removeCoupon(): void
    {
        session()->forget(self::COUPON);
    }

    // ------------------------------------------------------------------ shipping

    public function shippingMethod(): ?ShippingMethod
    {
        $methods = ShippingMethod::available();

        return $methods->firstWhere('id', (int) session(self::SHIPPING)) ?? $methods->first();
    }

    public function setShipping(int $id): void
    {
        if (ShippingMethod::available()->firstWhere('id', $id)) {
            session([self::SHIPPING => $id]);
        }
    }

    public function freeShippingThreshold(): float
    {
        return (float) setting('checkout.free_shipping_over', 0);
    }

    // ------------------------------------------------------------------ totals

    public function totals(?ShippingMethod $method = null): array
    {
        $subtotal = $this->subtotal();
        $coupon = $this->coupon();
        $discount = $coupon ? $coupon->discountFor($subtotal) : 0.0;
        $method ??= $this->shippingMethod();

        $threshold = $this->freeShippingThreshold();
        $freeByThreshold = $threshold > 0 && $subtotal >= $threshold;
        $shipping = ($freeByThreshold || $coupon?->free_shipping || $subtotal <= 0)
            ? 0.0
            : ($method ? $method->costFor($subtotal) : 0.0);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => round(max(0, $subtotal - $discount) + $shipping, 2),
            'coupon' => $coupon?->code,
            'shipping_method' => $method,
            'free_shipping_left' => $threshold > 0 ? max(0, $threshold - $subtotal) : null,
            'free_shipping_progress' => $threshold > 0 ? min(100, (int) round($subtotal / $threshold * 100)) : null,
        ];
    }

    /** JSON payload consumed by the cart drawer. */
    public function toArray(): array
    {
        $t = $this->totals();

        return [
            'count' => $this->lines()->sum('quantity'),
            'items' => $this->lines()->map->toArray()->values()->all(),
            'subtotal' => money($t['subtotal']),
            'subtotal_raw' => $t['subtotal'],
            'discount' => $t['discount'] > 0 ? money($t['discount']) : null,
            'total' => money($t['total']),
            'coupon' => $t['coupon'],
            'free_shipping_left' => $t['free_shipping_left'] !== null ? money($t['free_shipping_left']) : null,
            'free_shipping_left_raw' => $t['free_shipping_left'],
            'free_shipping_progress' => $t['free_shipping_progress'],
        ];
    }

    private function put(array $items): void
    {
        session([self::ITEMS => $items]);
        $this->lines = null;
    }
}
