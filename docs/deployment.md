# Deployment notes

The step-by-step install for VPS, cPanel and Docker is in [`HowToSetup.md`](../HowToSetup.md). This page covers running the store in production.

## Performance

- Enable **OPcache**, plus **gzip/brotli** and **HTTP/2** on the web server. `public/.htaccess` already sets compression and one-year browser caching for assets.
- Run `php artisan optimize && php artisan filament:optimize` after every deploy and after every `.env` change.
- Run `php artisan shop:warm-images` after you import many products. It pre-creates the WebP thumbnails; otherwise they are made on the first page view.
- Optional: use Redis with `CACHE_STORE=redis` and `SESSION_DRIVER=redis`. Cloudflare in front gives you a free CDN.

## Security (already included)

- **`public/.htaccess`:**
  - forces HTTPS and sends HSTS
  - blocks hidden files (`.env`, `.git`)
  - runs **only `index.php`**, so a PHP file uploaded into `storage/` cannot run
  - sends security headers (X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy)
  - adds a CSP sandbox for SVG files
- `docs/cpanel-root.htaccess` (when the project sits in `public_html`) forwards every request into `public/`. If `mod_rewrite` is missing, it blocks everything instead.
- Checkout runs in a database transaction with row locks, so stock can't go below zero.
- The admin panel is only for `is_admin` users. Customers see only their own orders, and order tracking needs both the order number and the phone number.

**Your part:**

- `APP_DEBUG=false`
- strong admin passwords
- keep PHP up to date
- run `composer audit` from time to time

## Backups

```bash
mysqldump -u USER -p shopbd | gzip > shopbd-$(date +%F).sql.gz      # database
tar czf uploads-$(date +%F).tgz storage/app/public --exclude=cache    # uploaded images
```

On cPanel, use Backup or JetBackup, or a cron job that runs the commands above.

## Updating to a new version

```bash
php artisan down
# replace the code (git pull, or upload the new files – keep .env and storage/)
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize && php artisan filament:optimize
php artisan up
```

## Cron (optional)

```
* * * * * cd /path/to/shopbd && php artisan schedule:run >> /dev/null 2>&1
```
