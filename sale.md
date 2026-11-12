# ShopBD – Fast, SEO-ready Laravel eCommerce for Bangladesh

> Ready-to-launch single-vendor online store with COD, bKash/Nagad, a drag-and-drop home page builder and a full admin panel. Scores 97–100 on Google Lighthouse.

---

## Short description (≈160 characters)

Laravel 13 single-vendor eCommerce for Bangladesh: COD, bKash/Nagad, home page builder, full admin panel, Lighthouse 97–100, SEO 100, mobile-first.

## Description (300 characters)

Ready-to-launch Laravel 13 eCommerce for Bangladesh. COD, bKash, Nagad, one-page checkout, drag-and-drop home page builder and a full Filament admin to manage orders, stock and coupons. Lighthouse 97-100, SEO 100, mobile-first, WebP images, hardened security. Full source code included.

## Short description – Bangla

Laravel 13 diye banano Bangladesh er jonno ready eCommerce website. Cash on Delivery, bKash/Nagad, Inside/Outside Dhaka delivery charge. Home page, header, footer, color sob admin panel theke control kora jay. Mobile e khub fast (Lighthouse 97–100), SEO 100. Code ar database kinle-i shop chalu kora jay.

---

## Full description

ShopBD is a complete online store for selling physical products in Bangladesh. You can launch it without writing code.

It is built for the way people in Bangladesh actually buy online:

- **Payments:** Cash on Delivery, bKash, Nagad and bank transfer.
- **Checkout:** one page, guest checkout allowed, Bangladeshi phone validation and a dropdown of all 64 districts.
- **Ordering by phone:** customers can also order through WhatsApp or a phone call.

The store owner controls everything from the admin panel, including the home page layout, header, footer, menus, colours, fonts, payment methods, delivery charges, coupons and popups. Saving a change updates the site immediately.

Speed and search ranking were the two main design goals:

- **Speed:** server-rendered pages, images converted to WebP automatically, and only about 37 KB of CSS and JS.
- **Search:** structured data (JSON-LD) that Google understands for products, plus an automatic sitemap and a product feed for Google Shopping and the Facebook catalogue.

---

## Five differentiators (and where they are in the code)

1. **Drag-and-drop home page builder**: 12 section types, each with its own visibility per device, with no coding needed.
   Code: `app/Filament/Resources/HomeSections`, `app/Services/HomeSectionResolver.php`, `resources/views/sections/*`
2. **Abandoned checkout recovery**: when a visitor types a phone number at checkout and leaves, the admin sees it and can call them back. The record is converted automatically if they later order.
   Code: `CheckoutController::capture()`, `app/Models/IncompleteOrder.php`, `app/Filament/Resources/IncompleteOrders`
3. **Checkout built for Bangladesh**:
   - COD, bKash and Nagad with transaction ID capture
   - BD phone validation and 64 districts
   - Inside/Outside Dhaka delivery zones
   - a COD order is marked paid automatically when it is delivered

   Code: `CheckoutController::PHONE_REGEX`, `app/Services/CheckoutService.php`, `app/Models/Order.php`, `config/shop.php`
4. **Honest social proof**: the "someone just bought" popup is built only from real orders, never from fake counters.
   Code: `ProductController::recentSales()`, `salesPopup` in `resources/js/app.js`
5. **Speed pipeline**: every image is turned into responsive WebP automatically, caches clear themselves on save, and the main image is preloaded. The result is Lighthouse 97–100 on mobile.
   Code: `app/Support/Img.php`, `app/Support/CacheVersion.php`, `app/Console/Commands/WarmImages.php`

Bonus: **stock-safe checkout**. Stock rows are locked in a database transaction, so two buyers can't buy the last item (`CheckoutService::place()`), and stock comes back on cancel or return (`Order::adjustStock()`).

---

## Key features

### Storefront (customer side)
- Mobile-first, modern design: sticky bottom navigation, slide-out cart, quick view, live search with suggestions
- Product pages:
  - image gallery with zoom
  - variants (size / colour) with their own price and stock
  - specifications, reviews with photos-free star ratings, related and recently viewed products
- Shop pages:
  - categories (nested) and brands
  - filters (price, brand, rating, in stock, on sale) and sorting
- **One-page checkout:** guest allowed, BD phone check, 64 districts, delivery zones, coupon field, live totals
- Order tracking (order number + phone), customer accounts with order history, wishlist
- Blog, CMS pages (About, Privacy, Return policy…), contact form, newsletter

### Sales boosters (all switchable from admin)
- Buy Now button and a sticky "Buy" bar on mobile
- WhatsApp order / chat and call-to-order buttons
- Free-delivery progress bar ("Add ৳300 more for free delivery")
- Low-stock urgency, sale countdown timers, discount badges, sold counts
- "Someone just bought…" popup built from **real** orders, never fake ones
- Exit-intent coupon popup
- **Abandoned checkout capture:** if a visitor types a phone number at checkout and leaves, the admin sees it and can call them back
- Distraction-free checkout (no menu, no footer links)

### Admin panel (Filament 5)
- **Dashboard:**
  - today's and this month's revenue, pending orders, abandoned checkouts, low stock
  - sales chart, best sellers
- **Orders:**
  - status tabs, one-click Confirm / Ship / Deliver / Cancel, order timeline
  - printable invoice, call or WhatsApp the customer, bulk status change, CSV export
  - stock returns automatically on cancel or return
  - **manual orders** for phone and Facebook sales, with stock deducted automatically
- **Catalog:** products (variant generator, gallery, sale price with end date, stock, specs, SEO preview, duplicate), categories, brands, review moderation
- **Home page builder:**
  - 12 section types: hero slider, category showcase, product collections, flash sale with countdown, banner grid, promo banner, brands, testimonials, blog, newsletter, custom HTML…
  - drag to reorder, turn sections on and off, show on desktop only, mobile only or both
- **Theme, header and footer:**
  - brand colour, accent colour, font, corner style
  - 3 header layouts, announcement bar, mega menu, dropdown menus
  - footer columns, social links, payment badges
  - custom CSS and scripts
- **Marketing:** coupons (percentage, fixed or free delivery, with usage limits and dates), newsletter subscribers (CSV export), 301 redirects
- **Settings:** store info and logo, checkout rules, payment methods, delivery zones, SEO and tracking (Google Tag Manager, GA4, Facebook Pixel, Search Console)
- Customers and staff, contact messages, blog and pages

### SEO
- Clean URLs (`/product/slug`, `/category/slug`), editable meta title and description everywhere, with a Google preview
- Structured data (JSON-LD) Google understands:
  - Product (price, stock, rating, reviews)
  - BreadcrumbList, Organization, WebSite search box, BlogPosting
- `sitemap.xml` (with images), dynamic `robots.txt`, Google Merchant / Facebook catalogue feed
- Canonical tags, filtered, cart and checkout pages hidden from search (`noindex`), Open Graph and Twitter cards

### Speed
- **Google Lighthouse (mobile):** Performance 97–100 · SEO 100 · Best Practices 100 · Accessibility 96+
- Page load (LCP) 1.4–2.6 s, layout shift (CLS) 0, server response about 20–50 ms
- Automatic responsive WebP images, lazy loading, the main image preloaded
- Smart caching that clears itself when the admin saves anything
- No jQuery and no heavy slider libraries; pages linked from the current page are pre-fetched, so they open almost instantly

### Security
- **Hardened `.htaccess`:**
  - forces HTTPS
  - blocks `.env`, `.git` and every hidden file
  - lets no PHP file run except `index.php`, so an uploaded shell cannot run
  - sends security headers and adds a script sandbox for uploaded SVG files
- Checkout runs in a database transaction that locks stock rows, so two buyers can't overbuy the last item
- Spam traps on the contact form, and a separate admin role for access to the admin panel
- 20 automated tests (171 assertions) cover:
  - the checkout, coupons and stock
  - every admin screen
  - SEO output

---

## Tech stack

**In one line (for forms):**
Laravel 13, PHP 8.3+, Filament 5, Livewire 4, Blade, Tailwind CSS 4, Alpine.js 3, Vite 8, MySQL / MariaDB, Intervention Image 3, PHPUnit 12, Apache (.htaccess)

| Layer | Technology | Version | Used for |
|---|---|---|---|
| Language | PHP | 8.3+ (built on 8.5, runs on 8.4) | Server-side code |
| Backend framework | Laravel | 13.42 | Routing, Eloquent ORM, sessions, cache, mail, queues |
| Admin panel | Filament | 5.11 | Complete admin: resources, forms, tables, dashboard widgets |
| Reactive UI (admin) | Livewire | 4.4 | Live admin forms and tables without page reloads |
| Templates | Blade | (Laravel) | Server-rendered storefront pages (fast, SEO-friendly) |
| CSS | Tailwind CSS | 4.3 | Styling, with theme colours and fonts set from the admin |
| JavaScript | Alpine.js (+ Collapse plugin) | 3.17 | Cart drawer, sliders, quick view, live search, popups, checkout |
| Build tool | Vite (laravel-vite-plugin) | 8.3 | Bundling and minifying CSS and JS |
| Database | MySQL / MariaDB | MySQL 8+ / MariaDB 10.4+ | All store data; full-text product search |
| Image processing | Intervention Image (GD driver) | 3.11 | Automatic responsive WebP thumbnails |
| Cache / session | File (default), Redis supported | – | Caching home sections, menus and settings |
| Mail | Laravel Mail (SMTP) | – | Order confirmation emails |
| Testing | PHPUnit | 12.5 | 20 feature tests, 171 assertions |
| Code style | Laravel Pint | 1.x | Code formatter (included as a dev tool) |
| Web server | Apache (cPanel) or Nginx | 2.4+ | Hardened `.htaccess` included |
| SEO | JSON-LD (schema.org), XML sitemap, Open Graph | – | Search engines and social sharing |
| Analytics (optional) | Google Tag Manager, GA4, Facebook Pixel | – | Configured from admin settings |
| Performance testing | Google Lighthouse, Puppeteer | – | Speed and layout checks during development |
| AI development tool | Claude Code (Anthropic) | – | AI pair programmer used to build the project |

---

## Project development details

**How it was built.** ShopBD was built with AI-assisted development: Claude Code (Anthropic) wrote the code under the project owner's direction. The owner set the requirements (a fast, SEO-first, mobile-first store for Bangladesh, fully controlled from the admin panel). The AI then designed, coded, tested and tuned the project step by step. After each step, the result was checked in a real browser and with automated tests before moving on.

**Timeline.** It took about 2 days (November 2026), in these stages:

1. **Planning:** database design, choice of tech stack and the list of features needed for the Bangladesh market (COD, bKash/Nagad, Inside/Outside Dhaka delivery, phone-based checkout)
2. **Backend:**
   - 3 groups of database migrations
   - Eloquent models with stock and price logic
   - cart, checkout and coupon services
   - an order lifecycle where stock returns on cancel or return, and a COD order is marked paid when delivered
3. **Admin panel:**
   - Filament 5 resources for every entity
   - order quick actions, manual orders and CSV export
   - the home page builder
   - theme, header, footer, sales-booster and SEO settings pages
   - dashboard widgets
4. **Storefront:**
   - Blade templates with Tailwind CSS v4 and Alpine.js
   - 12 home page section types, product and shop pages, cart drawer, one-page checkout
   - accounts, blog and CMS pages
5. **SEO:** JSON-LD structured data, sitemaps, robots.txt, product feed, canonical and noindex rules, redirect manager
6. **Speed tuning:**
   - measured with Google Lighthouse and fixed until mobile Performance reached 97–100
   - automatic WebP images with srcset, preloading of the main image, an SVG icon sprite, caching that clears itself, prefetching of linked pages
7. **Accessibility and quality:** colour-contrast fixes (WCAG), heading order, tap-target sizes, mobile layout checks with Puppeteer screenshots
8. **Testing:** 20 automated feature tests (171 assertions) covering:
   - checkout, coupons, stock deduction and restock, variants, registration
   - every admin screen, settings, order actions
   - SEO output
9. **Deployment hardening:**
   - `.htaccess` files for cPanel, tested on a real Apache + PHP-FPM setup
   - HTTPS enforcement, blocking of sensitive files, a PHP whitelist that lets only `index.php` run, security headers

**Code size and structure.** About 13,000 lines of code across:

- models, services and controllers
- Filament admin resources
- Blade views
- CSS, JS, migrations, seeders and tests

The code follows standard Laravel conventions, so any Laravel developer can maintain or extend it. There is no obfuscation and no license lock.

**Tools used:**
- Laravel 13, Filament 5, Tailwind CSS v4, Alpine.js, Vite
- PHPUnit, Google Lighthouse, Puppeteer
- Claude Code (AI pair programmer)

---

## What's included

- Full source code (no encryption, no license lock)
- Demo data seeder:
  - 184 products, categories, brands and banners
  - blog posts, reviews and 36 sample orders
- `database/schema.sql` (full schema) and `database/seed.sql` (demo data), plus the demo product photos
- `HowToSetup.md`, with two install paths (VPS/cPanel and Docker) and the `docker-compose.yml` setup
- Hardened `.htaccess` files for cPanel (`public/` and `docs/cpanel-root.htaccess`)
- `README.md`, `CHANGELOG.md`, `LICENSE-NOTE.md`, and `docs/` (ERD, payments, deployment)
- `CLAUDE.md` / `AGENTS.md` for AI coding tools
- Automated test suite (20 feature tests)

## Requirements

- PHP **8.3 or newer** with the GD (WebP), mbstring, pdo_mysql, fileinfo and intl extensions
- MySQL 8 or MariaDB 10.4+
- Composer (Node.js only if you change the design; the built CSS and JS are included)
- SSL certificate (free AutoSSL on cPanel is fine)

## Installation (about 10 minutes)

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate   # set database details in .env
php artisan migrate --seed       # --seed adds the demo store
php artisan storage:link
php artisan optimize
```

Then log in to `/admin`, change the admin password, and upload your logo and colours.

## Demo

- **Live demo:** _add your demo URL here_
- **Admin:** _demo admin URL_ – _demo email / password_
- **Customer:** _demo phone / password_

---

## Pricing (suggestion)

Prices are in BDT. Adjust them to the market.

| Package | What the buyer gets | Price |
|---|---|---|
| Regular | Source code + documentation | ৳ 5,000 – 8,000 |
| Standard | Code + installation on the buyer's cPanel + logo, colour and menu setup | ৳ 10,000 – 15,000 |
| Premium | Standard + bKash / SSLCommerz gateway or courier API integration + 1 month of support | ৳ 20,000+ |

## Support

- Free bug-fix support: _30 days_ (edit as you like)
- Installation help: included in Standard and Premium
- Custom work (payment gateway, SMS, courier API, Bangla translation): quoted separately

## Possible add-ons (not included, can be built on request)

- Online payment gateways (SSLCommerz, bKash Checkout API, Nagad API)
- SMS notifications for order status
- Courier booking (Pathao, Steadfast, RedX)
- Full Bangla storefront language

---

## FAQ

**Does it work on shared cPanel hosting?**
Yes, if the host offers PHP 8.3 or newer. Hardened `.htaccess` files for cPanel are included.

**Can I change the home page without coding?**
Yes. Add, remove and reorder sections from Admin → Home page builder.

**Is there online payment?**
COD, bKash, Nagad and bank transfer work as manual payments: the customer sends the money and enters the transaction ID. Automatic payment gateways can be added as an add-on.

**Is the demo product data included?**
Yes, for testing. Demo product photos come from the public dummyjson.com test catalogue. Replace them with your own products before going live.

**Can it be multi-vendor?**
No. It is a single-vendor store, built for one shop owner.

---

## Tags

laravel, ecommerce, laravel-13, filament, bangladesh, cod, bkash, nagad, online-shop, seo, tailwind, alpinejs, single-vendor, page-builder, fast, mobile-first

## Screenshots to add

1. Home page – desktop
2. Home page – mobile
3. Product page with variants and the Buy Now button
4. Cart drawer with the free-delivery bar
5. One-page checkout
6. Admin dashboard
7. Admin orders list and order view
8. Home page builder
9. Theme, header and footer settings
10. Lighthouse score screenshot
