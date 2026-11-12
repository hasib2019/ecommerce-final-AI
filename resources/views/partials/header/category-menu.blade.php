@if(! empty($h['show_category_menu']) && $categories->isNotEmpty())
<div class="relative mr-2" x-data="{ open: false, active: {{ $categories->first()->id }} }" @mouseenter="open = true" @mouseleave="open = false">
    <button type="button" @click="open = ! open" class="flex h-9 items-center gap-2 rounded-btn bg-primary-500 px-4 text-sm font-semibold text-white hover:bg-primary-600" :aria-expanded="open">
        {{ icon('grid', 'size-4.5') }} All Categories {{ icon('chevron-down', 'size-3.5', 2) }}
    </button>
    <div x-cloak x-show="open" x-transition.opacity.duration.150ms class="absolute left-0 top-full z-50 pt-2">
        <div class="flex w-[min(52rem,90vw)] overflow-hidden rounded-card border border-slate-200 bg-white shadow-2xl">
            <ul class="w-64 shrink-0 border-r border-slate-100 bg-slate-50/60 py-2">
                @foreach($categories as $cat)
                    <li>
                        <a href="{{ route('category.show', $cat->slug) }}" @mouseenter="active = {{ $cat->id }}"
                           class="flex items-center justify-between px-4 py-2.5 text-sm font-medium"
                           :class="active === {{ $cat->id }} ? 'bg-white text-primary-600' : 'text-slate-700'">
                            <span class="flex items-center gap-2.5">
                                @if($cat->image)<img src="{{ img($cat->image, 160) }}" alt="" class="size-6 rounded object-cover" width="24" height="24" loading="lazy">@endif
                                {{ $cat->name }}
                            </span>
                            @if($cat->children->isNotEmpty()){{ icon('chevron-right', 'size-3.5') }}@endif
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="flex-1 p-5">
                @foreach($categories as $cat)
                    <div x-show="active === {{ $cat->id }}" @if(! $loop->first) x-cloak @endif>
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900">{{ $cat->name }}</h3>
                            <a href="{{ route('category.show', $cat->slug) }}" class="text-xs font-semibold text-primary-600 hover:underline">View all →</a>
                        </div>
                        @if($cat->children->isNotEmpty())
                            <div class="grid grid-cols-3 gap-3">
                                @foreach($cat->children as $child)
                                    <a href="{{ route('category.show', $child->slug) }}" class="group flex flex-col items-center gap-2 rounded-lg p-2 text-center hover:bg-slate-50">
                                        <span class="size-16 overflow-hidden rounded-full bg-slate-100">
                                            @if($child->image)<img src="{{ img($child->image, 160) }}" alt="" class="size-full object-cover transition group-hover:scale-110" width="64" height="64" loading="lazy">@endif
                                        </span>
                                        <span class="text-xs font-medium text-slate-700 group-hover:text-primary-600">{{ $child->name }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <a href="{{ route('category.show', $cat->slug) }}" class="block overflow-hidden rounded-card bg-slate-100">
                                @if($cat->image)<img src="{{ img($cat->image, 640) }}" alt="{{ $cat->name }}" class="aspect-[2/1] w-full object-cover" loading="lazy" width="640" height="320">@endif
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif
