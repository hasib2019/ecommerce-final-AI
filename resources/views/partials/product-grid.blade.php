<div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 {{ ($cols ?? 5) >= 5 ? 'lg:grid-cols-5' : '' }} {{ ($cols ?? 5) >= 6 ? 'xl:grid-cols-6' : '' }}">
    @foreach($products as $product)
        @include('partials.product-card', ['product' => $product, 'eager' => ($eagerCount ?? 0) > $loop->index])
    @endforeach
</div>
