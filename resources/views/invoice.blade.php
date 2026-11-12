<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        *{box-sizing:border-box}body{font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#0f172a;margin:0;background:#f1f5f9;font-size:14px}
        .page{max-width:800px;margin:24px auto;background:#fff;padding:40px;border-radius:12px}
        .row{display:flex;justify-content:space-between;gap:24px}.muted{color:#64748b}.brand{font-size:24px;font-weight:800;color:{{ setting('appearance.primary_color') }}}
        h1{font-size:28px;margin:0;letter-spacing:2px}table{width:100%;border-collapse:collapse;margin-top:28px}
        th{background:#f8fafc;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#64748b}
        th,td{padding:10px 12px;border-bottom:1px solid #e2e8f0}td.r,th.r{text-align:right}
        .totals{margin-left:auto;width:280px;margin-top:16px}.totals div{display:flex;justify-content:space-between;padding:5px 0}
        .grand{font-size:18px;font-weight:800;border-top:2px solid #0f172a;margin-top:6px;padding-top:10px!important}
        .badge{display:inline-block;padding:3px 10px;border-radius:99px;background:#f1f5f9;font-size:12px;font-weight:600}
        .actions{max-width:800px;margin:16px auto 0;text-align:right}.actions button{background:#0f172a;color:#fff;border:0;padding:10px 18px;border-radius:8px;font-weight:600;cursor:pointer}
        @media print{body{background:#fff}.page{margin:0;padding:0;border-radius:0}.actions{display:none}}
    </style>
</head>
<body>
<div class="actions"><button onclick="window.print()">Print / Save PDF</button></div>
<div class="page">
    <div class="row">
        <div>
            @if(setting('general.logo'))
                <img src="{{ img(setting('general.logo'), 480) }}" alt="" style="height:40px">
            @else
                <div class="brand">{{ setting('general.site_name') }}</div>
            @endif
            <p class="muted" style="margin:8px 0 0;line-height:1.6">{{ setting('general.address') }}<br>{{ setting('general.phone') }} · {{ setting('general.email') }}</p>
        </div>
        <div style="text-align:right">
            <h1>INVOICE</h1>
            <p style="margin:8px 0 0;line-height:1.7"><strong>#{{ $order->order_number }}</strong><br><span class="muted">{{ $order->created_at->format('d M Y, h:i A') }}</span><br><span class="badge">{{ $order->status->getLabel() }}</span> <span class="badge">{{ $order->payment_status->getLabel() }}</span></p>
        </div>
    </div>

    <div class="row" style="margin-top:32px">
        <div>
            <div class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em">Bill & ship to</div>
            <p style="margin:6px 0 0;line-height:1.6"><strong>{{ $order->customer_name }}</strong><br>{{ $order->customer_phone }}@if($order->customer_email)<br>{{ $order->customer_email }}@endif<br>{{ $order->shipping_address }}{{ $order->city ? ', '.$order->city : '' }}</p>
        </div>
        <div style="text-align:right">
            <div class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em">Payment</div>
            <p style="margin:6px 0 0;line-height:1.6">{{ $order->paymentLabel() }}@if($order->transaction_id)<br>TrxID: {{ $order->transaction_id }}@endif<br>{{ $order->shipping_method }}</p>
        </div>
    </div>

    <table>
        <thead><tr><th>#</th><th>Item</th><th class="r">Price</th><th class="r">Qty</th><th class="r">Total</th></tr></thead>
        <tbody>
        @foreach($order->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->product_name }}@if($item->variant_name)<br><span class="muted">{{ $item->variant_name }}</span>@endif @if($item->sku)<br><span class="muted" style="font-size:12px">SKU: {{ $item->sku }}</span>@endif</td>
                <td class="r">{{ money($item->price) }}</td>
                <td class="r">{{ $item->quantity }}</td>
                <td class="r">{{ money($item->total) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><span class="muted">Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
        @if($order->discount > 0)<div><span class="muted">Discount {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</span><span>−{{ money($order->discount) }}</span></div>@endif
        <div><span class="muted">Delivery</span><span>{{ money($order->shipping_cost) }}</span></div>
        <div class="grand"><span>Total</span><span>{{ money($order->total) }}</span></div>
        @if($order->payment_method === 'cod' && $order->payment_status->value !== 'paid')
            <div style="font-weight:700;color:#dc2626"><span>Collect (COD)</span><span>{{ money($order->total) }}</span></div>
        @endif
    </div>

    @if($order->note)<p style="margin-top:24px"><strong>Customer note:</strong> {{ $order->note }}</p>@endif
    <p class="muted" style="margin-top:40px;text-align:center;font-size:12px">Thank you for shopping with {{ setting('general.site_name') }}!</p>
</div>
</body>
</html>
