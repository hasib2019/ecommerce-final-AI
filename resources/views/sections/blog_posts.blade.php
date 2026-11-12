<section class="container-x" aria-label="{{ $section->title ?: 'From our blog' }}">
    @include('partials.section-heading', ['link' => '/blog', 'linkText' => 'Read the blog'])
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($section->items as $post)
            @include('blog.card', ['post' => $post])
        @endforeach
    </div>
</section>
