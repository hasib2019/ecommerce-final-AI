<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Img;

class CartLine
{
    public float $price;

    public ?float $comparePrice;

    public function __construct(
        public string $key,
        public Product $product,
        public ?ProductVariant $variant,
        public int $quantity,
    ) {
        [$this->price, $this->comparePrice] = $product->pricingFor($variant);
    }

    public function total(): float
    {
        return round($this->price * $this->quantity, 2);
    }

    public function maxQuantity(): int
    {
        if (! $this->product->track_stock) {
            return 99;
        }

        $stock = $this->variant ? $this->variant->stock : $this->product->stock;

        return max(0, min(99, (int) $stock));
    }

    public function image(): ?string
    {
        return $this->variant?->image ?: $this->product->thumbnail;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant?->id,
            'name' => $this->product->name,
            'variant' => $this->variant?->name,
            'url' => $this->product->url,
            'image' => Img::url($this->image(), 160),
            'price' => $this->price,
            'price_formatted' => money($this->price),
            'compare_formatted' => $this->comparePrice ? money($this->comparePrice) : null,
            'quantity' => $this->quantity,
            'max' => $this->maxQuantity(),
            'total_formatted' => money($this->total()),
        ];
    }
}
