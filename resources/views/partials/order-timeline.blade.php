@php
    $steps = \App\Enums\OrderStatus::timeline();
    $current = $order->status;
    $failed = $current->releasesStock();
    $reachedIndex = array_search($current, $steps, true);
    $dates = $order->histories->groupBy(fn ($h) => $h->status->value)->map(fn ($g) => $g->last()->created_at);
@endphp
@if($failed)
    <div class="flex items-center gap-3 rounded-card border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
        {{ icon('info', 'size-5') }} This order was <strong>{{ strtolower($current->getLabel()) }}</strong>{{ $dates[$current->value] ?? null ? ' on '.$dates[$current->value]->format('M d, Y') : '' }}.
    </div>
@else
    <ol class="grid grid-cols-5 gap-1">
        @foreach($steps as $i => $step)
            @php($done = $reachedIndex !== false && $i <= $reachedIndex)
            <li class="flex flex-col items-center text-center">
                <div class="flex w-full items-center">
                    <span class="h-1 flex-1 {{ $i === 0 ? 'invisible' : ($done ? 'bg-emerald-500' : 'bg-slate-200') }}"></span>
                    <span class="grid size-8 shrink-0 place-items-center rounded-full text-xs font-bold {{ $done ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500' }} {{ $i === $reachedIndex ? 'ring-4 ring-emerald-500/20' : '' }}">
                        @if($done){{ icon('check', 'size-4', 3) }}@else{{ $i + 1 }}@endif
                    </span>
                    <span class="h-1 flex-1 {{ $loop->last ? 'invisible' : ($reachedIndex !== false && $i < $reachedIndex ? 'bg-emerald-500' : 'bg-slate-200') }}"></span>
                </div>
                <span class="mt-2 text-[11px] font-semibold sm:text-xs {{ $done ? 'text-slate-900' : 'text-slate-400' }}">{{ $step->getLabel() }}</span>
                @if(isset($dates[$step->value]))<span class="text-[10px] text-slate-400">{{ $dates[$step->value]->format('M d') }}</span>@endif
            </li>
        @endforeach
    </ol>
@endif
