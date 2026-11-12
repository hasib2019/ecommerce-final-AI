# ShopBD – Single-vendor e-commerce (Laravel 13 + Filament 5)

A fast, SEO-first, mobile-first online store built for the Bangladesh market
(৳ BDT, Cash on Delivery, bKash / Nagad, Inside / Outside Dhaka delivery).
Everything visible on the storefront – home page, header, footer, menus,
colours, fonts, payment methods, popups – is controlled from the admin panel.

| | |
|---|---|
| Storefront | Blade + Tailwind CSS v4 + Alpine.js (≈14 KB CSS + 23 KB JS gzipped) |
| Admin | Filament 5 (Livewire 4) at `/admin` |
| Stack | Laravel 13, PHP ≥ 8.3, MySQL / MariaDB |
| Lighthouse (mobile, gzip) | Performance 97–100 · SEO 100 · Best practices 100 · Accessibility 96+ |

---

> **Full install guide (VPS / cPanel / Docker, SQL import, first admin, branding): [HowToSetup.md](HowToSetup.md)** · more docs in [`docs/`](docs/README.md)

## Quick start (local)

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate   # then set DB_* in .env
php artisan migrate --seed          # demo store: 184 products, banners, orders…
php artisan storage:link
php artisan shop:warm-images        # pre-generate WebP thumbnails (optional, recommended)
php artisan serve                   # http://127.0.0.1:8000
```

> **XAMPP note:** XAMPP's bundled PHP is 8.2, Laravel 13 needs **PHP 8.3+**.
> Run the site with a newer PHP (e.g. Homebrew `php artisan serve`) or upgrade XAMPP.

**Demo logins**

| Role | Login | Password |
|---|---|---|
| Admin (`/admin`) | `admin@shop.test` | `password` |
| Customer (`/login`) | `01711111111` or `customer@shop.test` | `password` |

Change these before going live (Admin → Customers & staff).

Demo product data & photos come from the public [dummyjson.com](https://dummyjson.com) catalogue;
banners are generated locally. Without internet the seeder falls back to a small offline catalogue.

---

## What you control from the admin panel

| Menu | What it does |
|---|---|
| **Dashboard** | Today / month revenue, pending orders, abandoned checkouts, low stock, charts, best sellers |
| **Sales → Orders** | Status tabs, one-click Confirm / Ship / Deliver, timeline, invoice print, call / WhatsApp customer, bulk status change, CSV export, **manual orders** (phone / Facebook orders – stock is deducted automatically) |
| **Sales → Abandoned checkouts** | Customers who typed their phone at checkout but didn't order – call them back |
| **Catalog** | Products (variants generator, gallery, sale price + countdown, stock, specs, SEO preview), categories (nested), brands, reviews moderation |
| **Storefront → Home page builder** | 12 drag-and-drop section types: hero slider, category showcase, product collections (latest / featured / best-selling / on-sale / top-rated / category / hand-picked), flash sale with countdown, banner grid, promo banner, brands, testimonials, blog, newsletter, custom HTML. Per-section visibility (desktop / mobile) & background |
| **Storefront → Theme, header & footer** | Brand colour, accent, font, corner style · 3 header layouts · announcement bar · mega menu · main menu with dropdowns · footer columns, social links, payment badges · mobile bottom bar · custom CSS / scripts |
| **Storefront → Pages / Blog** | CMS pages (`/about-us`, policies…) and SEO blog |
| **Marketing → Sales boosters** | Buy-Now, sticky mobile buy bar, WhatsApp order / chat, call-to-order, real "someone just bought" popup, exit-intent coupon popup, trust badges |
| **Marketing** | Coupons (%, fixed, free delivery, limits, dates), newsletter subscribers (CSV), SEO 301 redirects |
| **Settings** | Store info & logo, currency, catalog options, checkout rules, payments (COD / bKash / Nagad / bank), SEO & tracking (GTM, GA4, Facebook Pixel, Search Console), delivery zones |

## Conversion features (built in)

* Guest checkout on **one page**, Bangladeshi phone validation, district dropdown, distraction-free checkout layout
* Free-delivery progress bar in cart drawer, coupon field, live totals
* Low-stock urgency, sale countdowns, discount / savings badges, sold counts, verified-buyer reviews
* Slide-out cart, quick view, wishlist (guest + account), recently viewed, related products, live search
* Abandoned-checkout capture, order tracking by order number + phone
* Real social proof only – no fake counters

## SEO (built in)

* Server-rendered HTML, clean URLs (`/product/slug`, `/category/slug`, `/about-us`)
* Editable meta title / description per product, category, brand, page and post with Google preview
* Canonicals, `noindex` on filtered / sorted / cart / checkout / account pages, `rel=prev/next`
* JSON-LD: Organization, WebSite + SearchAction, Product (offers, rating, reviews), BreadcrumbList, BlogPosting
* `/sitemap.xml` (index + products / categories / brands / pages / posts, with images), dynamic `/robots.txt`
* Google Merchant / Facebook catalogue feed: `/feed/products.xml`
* 301 redirect manager, Open Graph + Twitter cards, semantic headings, alt text

## Speed (built in)

* Every image is converted to responsive **WebP** (`srcset`) on first use; `shop:warm-images` pre-generates them
* LCP image preloaded with `fetchpriority=high`, everything else lazy-loaded, explicit width/height (CLS 0)
* Home sections, menus, settings and related products are cached; caches refresh automatically when you save in the admin
* SVG icon sprite, no jQuery / slider libraries, non-blocking fonts, Speculation Rules prefetch for instant page loads

---

## Going live checklist

```bash
# .env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
CACHE_STORE=redis        # or file
SESSION_DRIVER=redis     # or file
MAIL_MAILER=smtp         # order emails
QUEUE_CONNECTION=database  # then run: php artisan queue:work

composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize && php artisan filament:optimize
php artisan shop:warm-images
```

* Enable **gzip/brotli**, **HTTP/2** and **OPcache** on the server (`public/.htaccess` already has Apache rules)
* `public/.htaccess` also forces HTTPS, blocks hidden files and any PHP file except `index.php`, and sends security headers – make sure SSL is active first
* **cPanel with the project inside `public_html`:** copy the project-root `.htaccess` to `public_html/.htaccess`
  (it is git-ignored because cPanel writes its PHP-handler block into that file). It forwards everything into
  `public/` – never block `/storage` there, that is where all images are served from
* Point the document root to `/public`, use HTTPS, put Cloudflare in front for a free CDN
* Change the demo admin password, set your logo / colours / contact info, then delete demo products if needed
* Submit `https://your-domain.com/sitemap.xml` in Google Search Console

## Tests

```bash
php artisan test     # uses the MySQL database "ecommerce_ai_test" (see phpunit.xml)
```

18 feature tests cover every storefront page, SEO markup & endpoints, the full checkout
(coupon, stock deduction, cancel → restock), variants, registration, every admin screen,
settings → storefront, product & home-section editing, order status actions and manual orders.

## Extending

* **Online payment gateway (SSLCommerz / bKash API):** add a method in `CheckoutService::paymentMethods()` and redirect after `place()`
* **SMS notifications:** hook into `Order::booted()` (status changes) or `CheckoutService::notify()`
* **Courier (Pathao / Steadfast):** add an action to `ViewOrder` header actions
* Storefront text uses plain Blade – translate to Bangla by editing the views or wrapping strings in `__()`
