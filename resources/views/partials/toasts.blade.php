<div x-data class="pointer-events-none fixed inset-x-0 top-3 z-[90] flex flex-col items-center gap-2 px-3" aria-live="polite">
    <template x-for="t in $store.toast.items" :key="t.id">
        <div class="pointer-events-auto flex max-w-sm items-center gap-2.5 rounded-xl px-4 py-3 text-sm font-medium text-white shadow-xl animate-slide-up"
             :class="t.type === 'error' ? 'bg-rose-600' : 'bg-slate-900'">
            <span x-show="t.type !== 'error'" class="grid size-5 shrink-0 place-items-center rounded-full bg-emerald-500">{{ icon('check', 'size-3.5', 3) }}</span>
            <span x-show="t.type === 'error'" class="shrink-0">{{ icon('info', 'size-5') }}</span>
            <span x-text="t.message"></span>
        </div>
    </template>
</div>
