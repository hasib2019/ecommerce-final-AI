<button type="button" class="-ml-2 rounded-full p-2 text-slate-700 hover:bg-slate-100 {{ ($always ?? false) ? '' : 'md:hidden' }}" @click="$store.ui.menu = true" aria-label="Open menu">
    {{ icon('menu', 'size-6') }}
</button>
