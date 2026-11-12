@php($cols = (int) $section->get('columns', 5))
<section class="container-x" aria-label="{{ $section->title ?: 'Products' }}">
    @include('partials.section-heading', ['link' => $section->get('view_all_url'), 'linkText' => $section->get('view_all_text') ?: 'View all'])
    @if($section->get('layout', 'slider') === 'grid')
        @include('partials.product-grid', ['products' => $section->items, 'cols' => $cols])
    @else
        @include('partials.product-row', ['products' => $section->items, 'cols' => $cols])
    @endif
</section>
