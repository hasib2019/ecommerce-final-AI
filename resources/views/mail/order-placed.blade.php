<x-mail::message>
@if($forAdmin)
# New order received 🎉

**{{ $order->customer_name }}** ({{ $order->customer_phone }}) placed order **{{ $order->order_number }}**.
@else
# Thank you for your order, {{ $order->customer_name }}!

Your order **{{ $order->order_number }}** has been received. {{ setting('checkout.success_message') }}
@endif

<x-mail::table>
| Product | Qty | Total |
|:--------|:---:|------:|
@foreach($order->items as $item)
| {{ $item->product_name }}{{ $item->variant_name ? ' ('.$item->variant_name.')' : '' }} | {{ $item->quantity }} | {{ money($item->total) }} |
@endforeach
| Subtotal | | {{ money($order->subtotal) }} |
@if($order->discount > 0)
| Discount ({{ $order->coupon_code }}) | | −{{ money($order->discount) }} |
@endif
| Shipping | | {{ money($order->shipping_cost) }} |
| **Total** | | **{{ money($order->total) }}** |
</x-mail::table>

**Delivery to:** {{ $order->shipping_address }}{{ $order->city ? ', '.$order->city : '' }}
**Payment:** {{ $order->paymentLabel() }}

@if($forAdmin)
<x-mail::button :url="url('/admin/orders/'.$order->order_number)">Open in admin</x-mail::button>
@else
<x-mail::button :url="route('track', ['order' => $order->order_number, 'phone' => $order->customer_phone])">Track your order</x-mail::button>
@endif

Thanks,<br>
{{ setting('general.site_name') }}
</x-mail::message>
