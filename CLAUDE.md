# ShopBD – guide for AI coding tools (Claude Code, Cursor, Copilot…)

Single-vendor eCommerce for Bangladesh. Laravel 13 (PHP 8.3+), Filament 5 admin at `/admin`, storefront in Blade + Tailwind CSS 4 + Alpine.js, MySQL/MariaDB.

## Commands
- `php artisan serve` – dev server · `npm run dev` / `npm run build` – assets (Vite)
- `php artisan test` – 20 feature tests (uses the MySQL database `ecommerce_ai_test`, see `phpunit.xml`)
- `php artisan migrate --seed` – demo store · `php artisan shop:warm-images` – pre-generate WebP thumbnails
- `php artisan optimize:clear` after changing config / routes in production-cached setups

## Where things live
- Storefront: `app/Http/Controllers/*`, views in `resources/views` (`layouts/app`, `partials/*`, home sections in `sections/*`)
- Business logic: `app/Services` (`Cart`, `CheckoutService`, `HomeSectionResolver`), `app/Models`, `app/Enums/OrderStatus`
- Helpers: `app/helpers.php` (`setting()`, `money()`, `img()`, `seo()`, `cart()`, `icon()`), `app/Support/*` (`Settings`, `CacheVersion`, `Img`, `Seo`, `Icon`)
- Admin: `app/Filament/Resources/*`, settings pages in `app/Filament/Pages/Settings/*`, widgets in `app/Filament/Widgets`
- Settings defaults: `config/shop.php` (stored as one JSON row per group in `settings`)

## Rules / gotchas
- Filament 5 injects closure parameters **by name**: use `$query`, `$get`, `$set`, `$record`, `$data` (a `$q` param arrives null).
- Use `TextEntry::helperText()` (there is no `description()`).
- Models cached by the storefront must be listed in `config/cache.php` → `serializable_classes`, otherwise they unserialize as `__PHP_Incomplete_Class`.
- Any model change that affects the storefront must bump the cache: use the `FlushesStorefrontCache` trait or `CacheVersion::bump()`.
- Horizontal scroll-snap rows that use `-mx-3 px-3` also need `scroll-px-3`, or Chrome reports no LCP.
- Products/categories/brands route by `slug`, orders by `order_number`.
- Stock changes go through `CheckoutService::place()` (locked transaction) and `Order::adjustStock()` – don't update stock ad hoc.
- Never block `/storage` in `.htaccess`; `public/.htaccess` only allows `index.php` to execute.
- Keep storefront pages server-rendered and light (no new JS frameworks); check Lighthouse after UI changes.
