<?php

namespace App\Http\Controllers;

use App\Models\IncompleteOrder;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Services\Cart;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public const PHONE_REGEX = '/^(?:\+?88)?01[3-9]\d{8}$/';

    public function __construct(private Cart $cart) {}

    public function index(Request $request)
    {
        if ($this->cart->isEmpty() || $this->cart->lines()->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Your cart is empty.');
        }

        seo()->title('Checkout')->noindex(false);

        $user = $request->user();
        $last = $user ? $user->orders()->latest()->first() : null;

        return view('checkout.index', [
            'lines' => $this->cart->lines(),
            'totals' => $this->cart->totals(),
            'shippingMethods' => ShippingMethod::available(),
            'paymentMethods' => CheckoutService::paymentMethods(),
            'prefill' => [
                'name' => old('name', $user?->name ?? $last?->customer_name),
                'phone' => old('phone', $user?->phone ?? $last?->customer_phone),
                'email' => old('email', $user?->email ?? $last?->customer_email),
                'address' => old('address', $user?->address ?? $last?->shipping_address),
                'city' => old('city', $user?->city ?? $last?->city ?? 'Dhaka'),
            ],
        ]);
    }

    /** Recalculate totals when shipping method changes. */
    public function summary(Request $request)
    {
        if ($id = (int) $request->input('shipping_method_id')) {
            $this->cart->setShipping($id);
        }

        $t = $this->cart->totals();

        return response()->json([
            'subtotal' => money($t['subtotal']),
            'discount' => $t['discount'] > 0 ? money($t['discount']) : null,
            'shipping' => $t['shipping'] > 0 ? money($t['shipping']) : 'Free',
            'total' => money($t['total']),
            'total_raw' => $t['total'],
            'coupon' => $t['coupon'],
        ]);
    }

    /** Save what the customer typed so far – the admin can call them if they abandon checkout. */
    public function capture(Request $request)
    {
        if (! setting('checkout.capture_incomplete')) {
            return response()->noContent();
        }

        $phone = preg_replace('/[\s\-]/', '', (string) $request->input('phone'));
        if (! preg_match(self::PHONE_REGEX, $phone) || $this->cart->isEmpty()) {
            return response()->noContent();
        }

        IncompleteOrder::updateOrCreate(
            ['session_id' => session()->getId()],
            [
                'name' => mb_substr((string) $request->input('name'), 0, 120) ?: null,
                'phone' => $phone,
                'email' => filter_var($request->input('email'), FILTER_VALIDATE_EMAIL) ?: null,
                'address' => mb_substr((string) $request->input('address'), 0, 500) ?: null,
                'cart' => $this->cart->lines()->map(fn ($l) => [
                    'product' => $l->product->name, 'variant' => $l->variant?->name, 'qty' => $l->quantity, 'price' => $l->price,
                ])->values()->all(),
                'total' => $this->cart->totals()['total'],
                'is_converted' => false,
            ]
        );

        return response()->noContent();
    }

    public function store(Request $request, CheckoutService $checkout)
    {
        $request->merge(['phone' => preg_replace('/[\s\-]/', '', (string) $request->input('phone'))]);
        $payments = CheckoutService::paymentMethods();
        $manual = ($payments[$request->input('payment_method')]['manual'] ?? false)
            && $request->input('payment_method') !== 'bank';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:'.self::PHONE_REGEX],
            'email' => [setting('checkout.require_email') ? 'required' : 'nullable', 'email', 'max:120'],
            'address' => ['required', 'string', 'min:8', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
            'shipping_method_id' => ['required', 'integer', Rule::exists('shipping_methods', 'id')->where('is_active', true)],
            'payment_method' => ['required', Rule::in(array_keys($payments))],
            'transaction_id' => [$manual ? 'required' : 'nullable', 'string', 'max:100'],
        ], [
            'phone.regex' => 'Please enter a valid Bangladeshi mobile number (e.g. 01712345678).',
            'address.min' => 'Please enter your full address (house, road, area).',
            'transaction_id.required' => 'Please enter the transaction ID of your payment.',
        ]);

        $data['phone'] = preg_replace('/^\+?88/', '', $data['phone']); // store as 01XXXXXXXXX
        $order = $checkout->place($data);

        return redirect()->route('checkout.success', $order);
    }

    public function success(Request $request, Order $order)
    {
        $owns = session('last_order') === $order->order_number
            || ($request->user() && $order->user_id === $request->user()->id);

        if (! $owns) {
            return redirect()->route('track');
        }

        $order->load('items');
        seo()->title('Order placed successfully')->noindex(false);

        // Fire analytics "purchase" only once.
        $firstView = ! session()->has('tracked_'.$order->order_number);
        session()->put('tracked_'.$order->order_number, true);

        return view('checkout.success', compact('order', 'firstView'));
    }
}
