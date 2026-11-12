<section class="container-x" aria-label="Newsletter">
    <div class="relative overflow-hidden rounded-card bg-gradient-to-r from-primary-500 to-primary-700 px-6 py-10 text-center text-white sm:px-12">
        @if($image = $section->get('image'))
            <img src="{{ img($image, 1280) }}" alt="" class="absolute inset-0 size-full object-cover opacity-20" loading="lazy" width="1280" height="400">
        @endif
        <div class="relative mx-auto max-w-xl">
            <h2 class="text-2xl font-extrabold sm:text-3xl">{{ $section->title ?: 'Join our newsletter' }}</h2>
            <p class="mt-2 text-sm text-white/85 sm:text-base">{{ $section->subtitle ?: $section->get('text') }}</p>
            <form x-data="newsletter" @submit.prevent="submit" class="mx-auto mt-6 flex max-w-md flex-col gap-2 sm:flex-row">
                <label for="nl-{{ $section->id }}" class="sr-only">Email</label>
                <input id="nl-{{ $section->id }}" type="email" x-model="email" required placeholder="Your email address" class="input border-0 py-3" :disabled="done">
                <button class="btn btn-dark shrink-0 py-3" :disabled="busy || done" x-text="done ? 'Subscribed ✓' : @js($section->get('button_text') ?: 'Subscribe')">Subscribe</button>
            </form>
            <p class="mt-2 text-xs text-white/80" x-data x-show="false"></p>
        </div>
    </div>
</section>
