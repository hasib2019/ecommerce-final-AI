<div class="grid gap-8 lg:grid-cols-[18rem_1fr]">
    <div>
        <div class="flex items-center gap-4">
            <span class="text-5xl font-extrabold text-slate-900">{{ number_format($product->rating_avg, 1) }}</span>
            <div>
                @include('partials.stars', ['rating' => $product->rating_avg, 'size' => 'text-lg'])
                <p class="text-sm text-slate-500">{{ $product->rating_count }} {{ \Illuminate\Support\Str::plural('review', $product->rating_count) }}</p>
            </div>
        </div>
        <div class="mt-4 space-y-1.5">
            @for($star = 5; $star >= 1; $star--)
                @php($count = $ratingBreakdown[$star] ?? 0)
                @php($pct = $product->rating_count ? round($count / $product->rating_count * 100) : 0)
                <div class="flex items-center gap-2 text-xs text-slate-600">
                    <span class="w-6">{{ $star }}★</span>
                    <span class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100"><span class="block h-full rounded-full bg-amber-400" style="width: {{ $pct }}%"></span></span>
                    <span class="w-8 text-right">{{ $count }}</span>
                </div>
            @endfor
        </div>

        <form method="POST" action="{{ route('product.review', $product->id) }}" class="mt-6 space-y-3 rounded-card bg-slate-50 p-4" x-data="{ rating: {{ old('rating', 5) }} }">
            @csrf
            <h3 class="font-bold text-slate-900">Write a review</h3>
            <div class="flex gap-1" role="radiogroup" aria-label="Your rating">
                @for($i = 1; $i <= 5; $i++)
                    <button type="button" @click="rating = {{ $i }}" class="text-2xl leading-none transition" :class="rating >= {{ $i }} ? 'text-amber-400' : 'text-slate-300'" aria-label="{{ $i }} star">★</button>
                @endfor
                <input type="hidden" name="rating" :value="rating">
            </div>
            <div>
                <label for="rv-name" class="sr-only">Your name</label>
                <input id="rv-name" name="name" value="{{ old('name', auth()->user()?->name) }}" required maxlength="80" placeholder="Your name" class="input @error('name') input-error @enderror">
            </div>
            <div>
                <label for="rv-comment" class="sr-only">Your review</label>
                <textarea id="rv-comment" name="comment" rows="3" required minlength="5" maxlength="2000" placeholder="What did you like or dislike?" class="input @error('comment') input-error @enderror">{{ old('comment') }}</textarea>
                @error('comment')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <button class="btn btn-dark w-full">Submit review</button>
        </form>
    </div>

    <div>
        @forelse($reviews as $review)
            <article class="border-b border-slate-100 py-4 first:pt-0 last:border-0">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="grid size-9 place-items-center rounded-full bg-primary-100 text-sm font-bold text-primary-700">{{ mb_strtoupper(mb_substr($review->name, 0, 1)) }}</span>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $review->name }}
                                @if($review->is_verified)<span class="badge ml-1 bg-emerald-100 text-emerald-700">{{ icon('check', 'size-3', 3) }} Verified buyer</span>@endif
                            </p>
                            @include('partials.stars', ['rating' => $review->rating])
                        </div>
                    </div>
                    <time class="text-xs text-slate-400" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->diffForHumans() }}</time>
                </div>
                @if($review->comment)<p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $review->comment }}</p>@endif
            </article>
        @empty
            <div class="py-10 text-center text-slate-500">
                <p class="text-3xl">⭐</p>
                <p class="mt-2 font-medium">No reviews yet</p>
                <p class="text-sm">Bought this product? Share your experience to help others.</p>
            </div>
        @endforelse
    </div>
</div>
