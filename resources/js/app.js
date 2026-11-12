import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

/* ------------------------------------------------------------------ helpers */

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

async function api(url, data = null, method = 'POST') {
    const opts = {
        method,
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    };
    if (data && method !== 'GET') {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(data);
    }
    const res = await fetch(url, opts);
    let json = {};
    try { json = await res.json(); } catch (_) { /* empty */ }
    if (!res.ok && !json.message && json.errors) {
        json.message = Object.values(json.errors).flat()[0];
    }
    return { ok: res.ok, status: res.status, json };
}

const store = {
    get(k, d = null) { try { return JSON.parse(localStorage.getItem(k)) ?? d; } catch (_) { return d; } },
    set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (_) { /* private mode */ } },
};

/** Push e-commerce events to GTM dataLayer + Facebook Pixel when configured. */
function track(event, payload = {}) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ ecommerce: null });
    window.dataLayer.push({ event, ecommerce: payload });
    const fbMap = { add_to_cart: 'AddToCart', begin_checkout: 'InitiateCheckout', view_item: 'ViewContent', add_to_wishlist: 'AddToWishlist', purchase: 'Purchase' };
    if (window.fbq && fbMap[event]) {
        window.fbq('track', fbMap[event], { value: payload.value, currency: payload.currency, content_ids: (payload.items || []).map(i => i.item_id), content_type: 'product' });
    }
}
window.shopTrack = track;

const cfg = window.SHOP || {};

/* ------------------------------------------------------------------ stores */

document.addEventListener('alpine:init', () => {
    Alpine.store('toast', {
        items: [],
        show(message, type = 'success') {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, type });
            setTimeout(() => (this.items = this.items.filter(t => t.id !== id)), 3200);
        },
    });

    Alpine.store('cart', {
        open: false,
        loading: false,
        loaded: false,
        busy: null,
        data: { count: cfg.cartCount || 0, items: [] },

        async load() {
            this.loading = true;
            const { json } = await api(cfg.routes.cartData, null, 'GET');
            this.data = json;
            this.loaded = true;
            this.loading = false;
        },
        show() {
            this.open = true;
            if (!this.loaded) this.load();
        },
        async add(productId, variantId = null, quantity = 1, buyNow = false) {
            this.busy = productId;
            const { ok, json } = await api(cfg.routes.cartAdd, { product_id: productId, variant_id: variantId, quantity, buy_now: buyNow });
            this.busy = null;
            if (!ok) {
                Alpine.store('toast').show(json.message || 'Could not add to cart', 'error');
                return false;
            }
            if (json.added) {
                track('add_to_cart', { currency: cfg.currency, value: json.added.price * quantity, items: [{ item_id: String(json.added.id), item_name: json.added.name, price: json.added.price, quantity }] });
            }
            if (json.redirect) {
                window.location.href = json.redirect;
                return true;
            }
            this.data = json.cart;
            this.loaded = true;
            this.open = true;
            return true;
        },
        async update(key, quantity) {
            this.busy = key;
            const { json } = await api(cfg.routes.cartUpdate, { key, quantity });
            this.busy = null;
            if (json.cart) this.data = json.cart;
            window.dispatchEvent(new CustomEvent('cart-changed'));
        },
        remove(key) { return this.update(key, 0); },
    });

    Alpine.store('wishlist', {
        ids: cfg.wishlist || [],
        has(id) { return this.ids.includes(id); },
        async toggle(id) {
            const { ok, json } = await api(cfg.routes.wishlistToggle, { product_id: id });
            if (!ok) return;
            this.ids = json.added ? [...this.ids, id] : this.ids.filter(i => i !== id);
            Alpine.store('toast').show(json.message);
            if (json.added) track('add_to_wishlist', { items: [{ item_id: String(id) }] });
        },
    });

    Alpine.store('ui', { menu: false, search: false, filters: false, quick: null });

    /* -------------------------------------------------------------- components */

    Alpine.data('searchBox', () => ({
        q: '',
        open: false,
        loading: false,
        results: { products: [], categories: [] },
        active: -1,
        timer: null,
        input() {
            clearTimeout(this.timer);
            if (this.q.trim().length < 2) { this.open = false; return; }
            this.timer = setTimeout(() => this.fetch(), 220);
        },
        async fetch() {
            this.loading = true;
            const { json } = await api(cfg.routes.suggest + '?q=' + encodeURIComponent(this.q.trim()), null, 'GET');
            this.results = json;
            this.loading = false;
            this.open = true;
            this.active = -1;
        },
        move(step) {
            const n = this.results.products?.length || 0;
            if (!n) return;
            this.active = (this.active + step + n) % n;
        },
        go() {
            if (this.active >= 0 && this.results.products[this.active]) {
                window.location.href = this.results.products[this.active].url;
                return;
            }
            this.$el.closest('form')?.submit();
        },
    }));

    /** Hero slider based on CSS scroll-snap (zero dependencies, touch friendly). */
    Alpine.data('slider', (count = 1, autoplay = true, interval = 5) => ({
        index: 0,
        count,
        timer: null,
        init() {
            if (autoplay && count > 1) this.play();
            this.$refs.track.addEventListener('scroll', () => {
                const w = this.$refs.track.clientWidth || 1;
                this.index = Math.round(this.$refs.track.scrollLeft / w);
            }, { passive: true });
        },
        play() { this.timer = setInterval(() => this.go(this.index + 1), interval * 1000); },
        stop() { clearInterval(this.timer); },
        go(i) {
            const n = (i + this.count) % this.count;
            this.$refs.track.scrollTo({ left: n * this.$refs.track.clientWidth, behavior: 'smooth' });
            this.index = n;
        },
    }));

    /** Horizontal product rows with arrow buttons. */
    Alpine.data('carousel', () => ({
        atStart: true,
        atEnd: false,
        init() {
            const el = this.$refs.track;
            const check = () => {
                this.atStart = el.scrollLeft <= 4;
                this.atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4;
            };
            el.addEventListener('scroll', check, { passive: true });
            requestAnimationFrame(check);
        },
        scroll(dir) {
            const el = this.$refs.track;
            el.scrollBy({ left: dir * el.clientWidth * 0.85, behavior: 'smooth' });
        },
    }));

    Alpine.data('countdown', (end) => ({
        d: '00', h: '00', m: '00', s: '00', done: false,
        init() {
            const target = new Date(end).getTime();
            const tick = () => {
                const diff = Math.max(0, target - Date.now());
                if (diff <= 0) { this.done = true; return; }
                const pad = n => String(n).padStart(2, '0');
                this.d = pad(Math.floor(diff / 864e5));
                this.h = pad(Math.floor((diff % 864e5) / 36e5));
                this.m = pad(Math.floor((diff % 36e5) / 6e4));
                this.s = pad(Math.floor((diff % 6e4) / 1e3));
                setTimeout(tick, 1000);
            };
            tick();
        },
    }));

    /** Product page: gallery, variant picker, quantity, add to cart / buy now. */
    Alpine.data('productPage', (config) => ({
        ...config,
        image: 0,
        qty: 1,
        selected: {},
        zoom: false,
        init() {
            // Pre-select the first available variant so the price is always accurate.
            const first = this.variants.find(v => v.stock > 0) || this.variants[0];
            if (first) this.selected = { ...first.options };
            if (window.shopTrack) track('view_item', { currency: cfg.currency, value: this.priceRaw, items: [{ item_id: String(this.id), item_name: this.name, price: this.priceRaw }] });
        },
        get variant() {
            if (!this.variants.length) return null;
            return this.variants.find(v => this.options.every(o => v.options[o.name] === this.selected[o.name])) || null;
        },
        get price() { return this.variant ? this.variant.price : this.basePrice; },
        get compare() { return this.variant ? this.variant.compare : this.baseCompare; },
        get stock() { return this.variant ? this.variant.stock : this.baseStock; },
        get available() { return this.stock > 0 && (!this.variants.length || !!this.variant); },
        isOptionAvailable(name, value) {
            return this.variants.some(v => v.options[name] === value && v.stock > 0 &&
                this.options.every(o => o.name === name || !this.selected[o.name] || v.options[o.name] === this.selected[o.name]));
        },
        pick(name, value) {
            this.selected = { ...this.selected, [name]: value };
            if (this.variant?.image) {
                this.variantImage = this.variant.image;
            }
        },
        variantImage: null,
        origin: '50% 50%',
        zoomable() { return window.matchMedia('(hover: hover) and (min-width: 1024px)').matches; },
        zoomAt(e) {
            if (!this.zoom) return;
            const r = e.currentTarget.getBoundingClientRect();
            this.origin = `${((e.clientX - r.left) / r.width) * 100}% ${((e.clientY - r.top) / r.height) * 100}%`;
        },
        onScroll() {
            const t = this.$refs.gallery;
            this.image = Math.round(t.scrollLeft / (t.clientWidth || 1));
        },
        scrollToImage(i) {
            this.variantImage = null;
            const t = this.$refs.gallery;
            t.scrollTo({ left: i * t.clientWidth, behavior: 'smooth' });
            this.image = i;
        },
        inc() { if (this.qty < Math.min(99, this.stock)) this.qty++; },
        dec() { if (this.qty > 1) this.qty--; },
        async add(buyNow = false) {
            if (this.variants.length && !this.variant) {
                Alpine.store('toast').show('Please select ' + this.options.map(o => o.name).join(' / '), 'error');
                return;
            }
            await Alpine.store('cart').add(this.id, this.variant?.id ?? null, this.qty, buyNow);
        },
        whatsapp(number, url) {
            const text = `Hi, I want to order:\n${this.name}${this.variant ? ' (' + this.variant.name + ')' : ''}\nQty: ${this.qty}\nPrice: ${this.price}\n${url}`;
            window.open(`https://wa.me/${number}?text=${encodeURIComponent(text)}`, '_blank');
        },
    }));

    /** Checkout page: live totals, incomplete-order capture, coupon. */
    Alpine.data('checkout', (initial) => ({
        ...initial,
        payment: initial.payment,
        shipping: initial.shipping,
        couponCode: '',
        couponOpen: false,
        couponMsg: '',
        submitting: false,
        captured: '',
        init() {
            track('begin_checkout', { currency: cfg.currency, value: this.totalRaw });
        },
        async refresh() {
            const { json } = await api(cfg.routes.checkoutSummary, { shipping_method_id: this.shipping });
            Object.assign(this.totals, json);
            this.totalRaw = json.total_raw;
        },
        capture() {
            const f = this.$refs.form;
            const data = { name: f.name.value, phone: f.phone.value, email: f.email?.value, address: f.address.value };
            const sig = JSON.stringify(data);
            if (sig === this.captured || data.phone.replace(/\D/g, '').length < 11) return;
            this.captured = sig;
            navigator.sendBeacon
                ? navigator.sendBeacon(cfg.routes.checkoutCapture, new Blob([JSON.stringify({ ...data, _token: csrf() })], { type: 'application/json' }))
                : api(cfg.routes.checkoutCapture, data);
        },
        async applyCoupon() {
            if (!this.couponCode.trim()) return;
            const { ok, json } = await api(cfg.routes.coupon, { code: this.couponCode });
            this.couponMsg = json.message;
            if (ok) { await this.refresh(); this.couponOpen = false; Alpine.store('toast').show(json.message); }
        },
        async removeCoupon() {
            await api(cfg.routes.coupon, {}, 'DELETE');
            await this.refresh();
        },
        submit(e) {
            if (this.submitting) { e.preventDefault(); return; }
            this.submitting = true;
        },
    }));

    /** "Someone in Dhaka just bought…" – uses real recent orders only. */
    Alpine.data('salesPopup', (interval = 18) => ({
        list: [], current: null, show: false, i: 0,
        async init() {
            if (store.get('sp_closed_until', 0) > Date.now()) return;
            setTimeout(async () => {
                const { json } = await api(cfg.routes.recentSales, null, 'GET');
                this.list = Array.isArray(json) ? json : [];
                if (this.list.length) this.next();
            }, 8000);
        },
        next() {
            this.current = this.list[this.i++ % this.list.length];
            this.show = true;
            setTimeout(() => { this.show = false; setTimeout(() => this.next(), interval * 1000); }, 5500);
        },
        close() { this.show = false; store.set('sp_closed_until', Date.now() + 864e5); this.list = []; },
    }));

    /** Exit-intent offer (desktop: cursor leaves the window; mobile: after a while). */
    Alpine.data('exitPopup', () => ({
        open: false, copied: false,
        init() {
            if (store.get('exit_seen_until', 0) > Date.now()) return;
            const trigger = () => {
                if (store.get('exit_seen_until', 0) > Date.now()) return;
                this.open = true;
                store.set('exit_seen_until', Date.now() + 3 * 864e5);
            };
            document.addEventListener('mouseout', (e) => { if (!e.relatedTarget && e.clientY < 8) trigger(); });
            if (window.matchMedia('(pointer: coarse)').matches) setTimeout(trigger, 45000);
        },
        copy(code) {
            navigator.clipboard?.writeText(code);
            this.copied = true;
        },
    }));

    Alpine.data('newsletter', () => ({
        email: '', done: false, msg: '', busy: false,
        async submit() {
            this.busy = true;
            const { ok, json } = await api(cfg.routes.newsletter, { email: this.email });
            this.busy = false;
            this.msg = json.message;
            this.done = ok;
        },
    }));

    Alpine.data('quickView', () => ({
        html: '', loading: false,
        async open(url) {
            Alpine.store('ui').quick = true;
            this.loading = true;
            this.html = '';
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            this.html = await res.text();
            this.loading = false;
        },
    }));
});

window.Alpine = Alpine;
Alpine.plugin(collapse);
Alpine.start();
