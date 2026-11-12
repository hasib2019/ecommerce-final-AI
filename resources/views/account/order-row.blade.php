<a href="{{ route('account.order', $order) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 p-4 hover:bg-slate-50">
    <span class="font-mono text-sm font-bold text-slate-900">#{{ $order->order_number }}</span>
    <span class="text-xs text-slate-500">{{ $order->created_at->format('M d, Y') }} · {{ $order->items_count }} {{ \Illuminate\Support\Str::plural('item', $order->items_count) }}</span>
    <span class="badge ml-auto {{ $order->status->badgeClass() }}">{{ $order->status->getLabel() }}</span>
    <span class="w-24 text-right text-sm font-bold">{{ money($order->total) }}</span>
</a>
