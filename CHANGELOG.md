# Changelog

This project follows [Semantic Versioning](https://semver.org/).

## [1.0.0] – 2026-11-13

First public release.

### Storefront
- Mobile-first storefront (Blade, Tailwind CSS 4, Alpine.js) with a sticky bottom navigation, slide-out cart, quick view and live search
- **Product pages:** gallery with zoom, variants (size and colour) each with its own price and stock, specifications, reviews, related and recently viewed products
- **Shop pages:** nested categories and brands, filters and sorting
- **One-page guest checkout:** Bangladeshi phone validation, 64 districts, delivery zones, coupons
- **Payments:** Cash on Delivery, bKash, Nagad and bank transfer, with transaction ID capture
- Order tracking, customer accounts, wishlist, blog, CMS pages, contact form, newsletter

### Sales boosters
- Buy Now and a sticky mobile buy bar, WhatsApp and call-to-order buttons, free-delivery progress bar
- Low-stock urgency, sale countdowns, real-order "just bought" popup, exit-intent coupon popup
- Abandoned checkout capture, so the admin can call customers back

### Admin panel (Filament 5)
- Dashboard with revenue, order, stock and abandoned-checkout figures, plus charts
- **Orders:**
  - status tabs, one-click status actions, order timeline, invoice
  - call or WhatsApp the customer, bulk actions, CSV export
  - manual orders with automatic stock deduction
- Home page builder with 12 section types (drag and drop, per-device visibility)
- Theme, header and footer, sales-booster, store/checkout and SEO settings
- Coupons, shipping zones, reviews, customers, pages, posts, subscribers, messages, 301 redirects

### SEO and performance
- JSON-LD (Product, Breadcrumb, Organization, WebSite, BlogPosting), sitemaps, dynamic robots.txt, product feed
- Automatic responsive WebP images, preloading of the main image, an SVG icon sprite, caches that clear themselves when something changes
- Google Lighthouse (mobile): Performance 97–100, SEO 100, Best Practices 100

### Security and setup
- Hardened `.htaccess` files:
  - HTTPS and HSTS
  - only `index.php` may run
  - hidden files blocked
  - security headers
- Stock locked during checkout, admin panel limited to admin users, data scoped to each user
- `database/schema.sql` and `database/seed.sql`, Docker setup, `HowToSetup.md`, 20 feature tests
- Optional private-subdomain gate (`docs/subdomain-gate.md`), off by default
