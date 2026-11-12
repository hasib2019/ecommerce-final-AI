<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderPlaced;
use App\Models\Coupon;
use App\Models\IncompleteOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutService
{
    public function __construct(private Cart $cart) {}

    /** Payment methods enabled in settings: key => [label, note, number] */
    public static function paymentMethods(): array
    {
        $methods = [];

        if (setting('checkout.cod_enabled')) {
            $methods['cod'] = ['label' => setting('checkout.cod_label', 'Cash on Delivery'), 'note' => setting('checkout.cod_note'), 'number' => null, 'manual' => false];
        }
        if (setting('checkout.bkash_enabled')) {
            $methods['bkash'] = ['label' => 'bKash', 'note' => setting('checkout.bkash_note'), 'number' => setting('checkout.bkash_number'), 'type' => setting('checkout.bkash_type'), 'manual' => true];
        }
        if (setting('checkout.nagad_enabled')) {
            $methods['nagad'] = ['label' => 'Nagad', 'note' => setting('checkout.nagad_note'), 'number' => setting('checkout.nagad_number'), 'manual' => true];
        }
        if (setting('checkout.bank_enabled')) {
            $methods['bank'] = ['label' => 'Bank Transfer', 'note' => setting('checkout.bank_details'), 'number' => null, 'manual' => true];
        }

        return $methods ?: ['cod' => ['label' => 'Cash on Delivery', 'note' => null, 'number' => null, 'manual' => false]];
    }

    /**
     * @param  array{name:string,phone:string,email:?string,address:string,city:?string,area:?string,note:?string,shipping_method_id:int,payment_method:string,transaction_id:?string}  $data
     */
    public function place(array $data): Order
    {
        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        $method = ShippingMethod::available()->firstWhere('id', (int) $data['shipping_method_id'])
            ?? $this->cart->shippingMethod();

        $order = DB::transaction(function () use ($lines, $data, $method) {
            // Lock and re-validate stock for every line.
            $products = Product::query()->whereIn('id', $lines->pluck('product.id'))->lockForUpdate()->get()->keyBy('id');
            $variantIds = $lines->pluck('variant.id')->filter()->all();
            $variants = $variantIds ? ProductVariant::query()->whereIn('id', $variantIds)->lockForUpdate()->get()->keyBy('id') : collect();

            foreach ($lines as $line) {
                $product = $products[$line->product->id];
                $available = $line->variant ? $variants[$line->variant->id]->stock : $product->stock;

                if ($product->track_stock && $available < $line->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "Sorry, only {$available} × {$product->name} left in stock. Please update your cart.",
                    ]);
                }
            }

            $totals = $this->cart->totals($method);

            $min = (float) setting('checkout.min_order', 0);
            if ($min > 0 && $totals['subtotal'] < $min) {
                throw ValidationException::withMessages(['cart' => 'Minimum order amount is '.money($min).'.']);
            }

            $order = Order::create([
                'user_id' => auth()->id(),
                'customer_name' => $data['name'],
                'customer_phone' => $data['phone'],
                'customer_email' => $data['email'] ?? null,
                'shipping_address' => $data['address'],
                'city' => $data['city'] ?? null,
                'area' => $data['area'] ?? null,
                'note' => $data['note'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'shipping_cost' => $totals['shipping'],
                'total' => $totals['total'],
                'coupon_code' => $totals['coupon'],
                'shipping_method' => $method?->name,
                'payment_method' => $data['payment_method'],
                'payment_status' => ($data['transaction_id'] ?? null) ? PaymentStatus::Pending : PaymentStatus::Unpaid,
                'transaction_id' => $data['transaction_id'] ?? null,
                'status' => OrderStatus::Pending,
                'ip_address' => request()->ip(),
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line->product->id,
                    'product_variant_id' => $line->variant?->id,
                    'product_name' => $line->product->name,
                    'variant_name' => $line->variant?->name,
                    'sku' => $line->variant?->sku ?: $line->product->sku,
                    'image' => $line->image(),
                    'price' => $line->price,
                    'quantity' => $line->quantity,
                    'total' => $line->total(),
                ]);

                $product = $products[$line->product->id];
                if ($line->variant) {
                    $variants[$line->variant->id]->decrement('stock', $line->quantity);
                } elseif ($product->track_stock) {
                    $product->decrement('stock', $line->quantity);
                }
                $product->increment('sold_count', $line->quantity);
                if ($line->variant) {
                    $product->syncStockFromVariants();
                }
            }

            if ($totals['coupon']) {
                Coupon::where('code', $totals['coupon'])->increment('used_count');
            }

            return $order;
        });

        IncompleteOrder::where('session_id', session()->getId())->update(['is_converted' => true, 'status' => 'converted']);
        $this->cart->clear();
        session()->put('last_order', $order->order_number);

        $this->notify($order);

        return $order;
    }

    private function notify(Order $order): void
    {
        try {
            $order->load('items');
            if ($order->customer_email) {
                Mail::to($order->customer_email)->send(new OrderPlaced($order));
            }
            if ($admin = setting('checkout.admin_email') ?: setting('general.email')) {
                Mail::to($admin)->send(new OrderPlaced($order, forAdmin: true));
            }
        } catch (Throwable $e) {
            report($e); // never block checkout on mail problems
        }
    }
}
