<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    public function __invoke(Request $request)
    {
        seo()->title('Track Your Order')
            ->description('Track the status of your '.setting('general.site_name').' order using your order number and phone number.');

        $order = null;
        $searched = $request->filled(['order', 'phone']);

        if ($searched) {
            $phone = preg_replace('/[\s\-]/', '', (string) $request->query('phone'));
            $phone = preg_replace('/^\+?88/', '', $phone);

            $order = Order::query()
                ->where('order_number', strtoupper(trim((string) $request->query('order'))))
                ->where(fn ($q) => $q->where('customer_phone', $phone)
                    ->orWhere('customer_phone', '88'.$phone)
                    ->orWhere('customer_phone', '+88'.$phone))
                ->with(['items', 'histories'])
                ->first();

            seo()->noindex();
        }

        return view('track', compact('order', 'searched'));
    }
}
