<div x-data x-cloak x-show="$store.ui.menu" class="fixed inset-0 z-[60] md:hidden" role="dialog" aria-modal="true" aria-label="Menu">
    <div x-show="$store.ui.menu" x-transition.opacity class="absolute inset-0 bg-slate-900/50" @click="$store.ui.menu = false"></div>
    <aside x-show="$store.ui.menu" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-x-full" x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="-translate-x-full"
           class="absolute inset-y-0 left-0 flex w-[85%] max-w-sm flex-col bg-white shadow-2xl" @keydown.escape.window="$store.ui.menu = false">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            @include('partials.header.logo')
            <button type="button" class="rounded-full p-2 hover:bg-slate-100" @click="$store.ui.menu = false" aria-label="Close menu">{{ icon('x', 'size-6') }}</button>
        </div>
        <div class="flex-1 overflow-y-auto" x-data="{ tab: 'categories' }">
            <div class="grid grid-cols-2 border-b border-slate-100 text-sm font-semibold">
                <button type="button" @click="tab = 'categories'" class="py-3" :class="tab === 'categories' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-slate-500'">Categories</button>
                <button type="button" @click="tab = 'menu'" class="py-3" :class="tab === 'menu' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-slate-500'">Menu</button>
            </div>
            <ul x-show="tab === 'categories'" class="divide-y divide-slate-100">
                @foreach($categories as $cat)
                    <li x-data="{ open: false }">
                        <div class="flex items-center">
                            <a href="{{ route('category.show', $cat->slug) }}" class="flex flex-1 items-center gap-3 px-4 py-3 text-sm font-medium text-slate-800">
                                @if($cat->image)<img src="{{ img($cat->image, 160) }}" alt="" class="size-8 rounded-lg object-cover" width="32" height="32" loading="lazy">@endif
                                {{ $cat->name }}
                            </a>
                            @if($cat->children->isNotEmpty())
                                <button type="button" class="px-4 py-3 text-slate-500" @click="open = ! open" :aria-expanded="open" aria-label="Show {{ $cat->name }} subcategories">
                                    <span class="block transition" :class="open && 'rotate-180'">{{ icon('chevron-down', 'size-4') }}</span>
                                </button>
                            @endif
                        </div>
                        @if($cat->children->isNotEmpty())
                            <ul x-show="open" x-collapse class="bg-slate-50 py-1">
                                @foreach($cat->children as $child)
                                    <li><a href="{{ route('category.show', $child->slug) }}" class="block py-2 pl-14 pr-4 text-sm text-slate-600">{{ $child->name }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
            <ul x-cloak x-show="tab === 'menu'" class="divide-y divide-slate-100">
                @foreach($menu as $item)
                    @continue(blank($item['label'] ?? null))
                    <li><a href="{{ menu_url($item['url'] ?? '#') }}" class="block px-4 py-3 text-sm font-medium {{ ! empty($item['highlight']) ? 'text-primary-600' : 'text-slate-800' }}">{{ $item['label'] }}</a></li>
                    @foreach(array_filter((array) ($item['children'] ?? []), fn ($c) => filled($c['label'] ?? null)) as $child)
                        <li><a href="{{ menu_url($child['url'] ?? '#') }}" class="block py-2.5 pl-8 pr-4 text-sm text-slate-600">{{ $child['label'] }}</a></li>
                    @endforeach
                @endforeach
                <li><a href="{{ route('track') }}" class="block px-4 py-3 text-sm font-medium text-slate-800">Track Order</a></li>
                <li><a href="{{ route('wishlist') }}" class="block px-4 py-3 text-sm font-medium text-slate-800">Wishlist</a></li>
            </ul>
        </div>
        <div class="space-y-2 border-t border-slate-100 p-4">
            @auth
                <a href="{{ route('account.index') }}" class="btn btn-outline w-full">{{ icon('user', 'size-4') }} My Account</a>
            @else
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('login') }}" class="btn btn-outline">Sign in</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
                </div>
            @endauth
            @if(setting('general.phone'))
                <a href="tel:{{ preg_replace('/[^\d+]/', '', setting('general.phone')) }}" class="flex items-center justify-center gap-2 pt-1 text-sm font-semibold text-slate-700">{{ icon('phone', 'size-4') }} {{ setting('general.phone') }}</a>
            @endif
        </div>
    </aside>
</div>
