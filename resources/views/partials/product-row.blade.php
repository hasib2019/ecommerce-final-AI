{{-- Horizontal scroll row with arrows; falls back to native swipe on touch. --}}
<div x-data="carousel" class="relative">
    <div x-ref="track" class="snap-row -mx-3 gap-3 px-3 pb-1 scroll-px-3 sm:-mx-0 sm:gap-4 sm:px-0 sm:scroll-px-0">
        @foreach($products as $product)
            <div class="row-item {{ ($cols ?? 5) >= 6 ? 'row-item-6' : (($cols ?? 5) >= 5 ? 'row-item-5' : '') }}">
                @include('partials.product-card', ['product' => $product])
            </div>
        @endforeach
    </div>
    <button type="button" x-show="!atStart" x-transition.opacity @click="scroll(-1)" class="absolute -left-4 top-[35%] z-10 hidden size-10 place-items-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-lg hover:text-primary-600 md:grid" aria-label="Previous">{{ icon('chevron-left', 'size-5', 2) }}</button>
    <button type="button" x-show="!atEnd" x-transition.opacity @click="scroll(1)" class="absolute -right-4 top-[35%] z-10 hidden size-10 place-items-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-lg hover:text-primary-600 md:grid" aria-label="Next">{{ icon('chevron-right', 'size-5', 2) }}</button>
</div>
