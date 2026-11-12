<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private Cart $cart) {}

    public function index()
    {
        seo()->title('Shopping Cart')->noindex();

        return view('cart.index', [
            'lines' => $this->cart->lines(),
            'totals' => $this->cart->totals(),
        ]);
    }

    public function data()
    {
        return response()->json($this->cart->toArray())->header('Cache-Control', 'no-store');
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'buy_now' => ['nullable', 'boolean'],
        ]);

        $product = Product::find($data['product_id']);
        if (! $product) {
            return $this->respond($request, 'Product not found.', false, 404);
        }

        $variant = ! empty($data['variant_id']) ? ProductVariant::find($data['variant_id']) : null;

        if ($error = $this->cart->add($product, $variant, (int) ($data['quantity'] ?? 1))) {
            return $this->respond($request, $error, false, 422);
        }

        if (! empty($data['buy_now'])) {
            return $request->expectsJson()
                ? response()->json(['ok' => true, 'redirect' => route('checkout')])
                : redirect()->route('checkout');
        }

        return $this->respond($request, 'Added to cart', true, 200, [
            'added' => [
                'name' => $product->name,
                'price' => $product->final_price,
                'id' => $product->id,
                'variant' => $variant?->name,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:40'],
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $this->cart->setQuantity($data['key'], $data['quantity']);

        return $this->respond($request, 'Cart updated');
    }

    public function remove(Request $request)
    {
        $this->cart->remove((string) $request->input('key'));

        return $this->respond($request, 'Item removed');
    }

    public function applyCoupon(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:50']]);

        if ($error = $this->cart->applyCoupon($data['code'])) {
            return $this->respond($request, $error, false, 422);
        }

        return $this->respond($request, 'Coupon applied!');
    }

    public function removeCoupon(Request $request)
    {
        $this->cart->removeCoupon();

        return $this->respond($request, 'Coupon removed');
    }

    private function respond(Request $request, string $message, bool $ok = true, int $status = 200, array $extra = [])
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => $ok, 'message' => $message, 'cart' => $this->cart->toArray(), ...$extra], $status);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }
}
