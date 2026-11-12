# How to set up ShopBD

There are two ways to install ShopBD:

- **A. VPS / cPanel**: a normal server with PHP and MySQL
- **B. Docker**: one command, with the app and MySQL in containers

Both end with the same result: a working store at your domain and the admin panel at `/admin`.

| You need | Version |
|---|---|
| PHP | **8.3 or newer**, with these extensions: `gd` (with WebP), `pdo_mysql`, `mbstring`, `intl`, `fileinfo`, `zip`, `bcmath`, `exif` |
| Composer | 2.x |
| MySQL / MariaDB | MySQL 8+ or MariaDB 10.4+ |
| Node.js | 20+ (**only** if you change CSS/JS; the built files in `public/build` are already included) |
| Web server | Apache 2.4 with `mod_rewrite` (the `.htaccess` files are included), or Nginx |

---

## A. VPS or cPanel

### 1. Upload and install the PHP packages

```bash
cd /var/www/shopbd                  # wherever you unzipped the project
composer install --no-dev --optimize-autoloader
```

Optional: rebuild the CSS/JS. Only do this if you edited files in `resources/`.

```bash
npm ci && npm run build
```

### 2. Environment file and app key

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```dotenv
APP_NAME="Your Shop"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shopbd
DB_USERNAME=shopbd_user
DB_PASSWORD=strong-password

FILESYSTEM_DISK=public
MAIL_MAILER=smtp          # plus MAIL_HOST / MAIL_USERNAME / MAIL_PASSWORD, for order emails
```

### 3. Database: pick one option

| Option | Commands | Result |
|---|---|---|
| **Migrate (recommended)** | `php artisan migrate --force` | An empty store |
| Migrate + demo store | `php artisan migrate --seed --force` | Demo store; downloads the demo photos (needs internet) |
| **Import SQL** (no SSH needed) | `mysql -u USER -p shopbd < database/schema.sql`, then optionally `mysql -u USER -p shopbd < database/seed.sql` | Empty store, or the demo store if you also import `seed.sql` |

- `database/schema.sql` creates every table, key and index in an empty database. You can also import it with phpMyAdmin.
- `database/seed.sql` adds the demo data:
  - 184 products with variants and stock, categories, brands
  - coupons, shipping zones, home page sections, pages, blog posts
  - 36 orders and 2 demo users
- The demo photos used by `seed.sql` are already in `storage/app/public`.

### 4. Storage link, cache and thumbnails

```bash
php artisan storage:link          # makes /storage/... images public
php artisan optimize              # caches config, routes, views
php artisan filament:optimize
php artisan shop:warm-images      # optional: pre-generates WebP thumbnails
chmod -R 775 storage bootstrap/cache
```

If `storage:link` says *"link already exists"* but images still don't show, the link is broken. Re-create it:

```bash
rm public/storage && php artisan storage:link
```

Do **not** add a trailing `/` after `public/storage` in that command.

### 5. Web server

**Best option:** point the domain's document root to the project's `public/` folder. The included `public/.htaccess` then handles everything:

- HTTPS redirect
- security headers
- gzip and browser caching
- blocking of hidden files and stray PHP files

**cPanel with the whole project inside `public_html`:** copy [`docs/cpanel-root.htaccess`](docs/cpanel-root.htaccess) to `public_html/.htaccess`. Keep the PHP-handler block that cPanel adds to that file.

> Never block `/storage` in `.htaccess`. All product images are served from it.

Nginx example:

```nginx
root /var/www/shopbd/public;
index index.php;
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { include fastcgi_params; fastcgi_pass unix:/run/php/php8.4-fpm.sock; fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; }
location ~ /\.(?!well-known) { deny all; }
location ~ ^/storage/.*\.php$ { deny all; }
```

### 6. Queue (optional)

Order emails are sent straight away with `QUEUE_CONNECTION=sync`. On a busy store, use the database queue:

1. Set `QUEUE_CONNECTION=database` in `.env`.
2. Run `php artisan queue:work` under Supervisor, or as a cPanel cron job every minute: `php artisan queue:work --stop-when-empty`.

---

## B. Docker

```bash
cp .env.example .env
docker compose up -d --build
```

Then open **http://localhost:8080**.

On the **first** start:

1. The MySQL container imports `database/schema.sql` and `database/seed.sql`, so you get the demo store.
2. The app container generates `APP_KEY`, runs any newer migrations, creates the storage link and caches the config.

Useful commands:

```bash
docker compose exec app php artisan tinker        # console
docker compose logs -f app                        # logs
docker compose down -v                            # stop and DELETE the database (fresh start)
```

To start with an **empty** store, remove the `02-seed.sql` line from `docker-compose.yml` before the first `up`.

Before using this in production:

- change the MySQL passwords in `docker-compose.yml`
- set `APP_URL` in `.env`
- put HTTPS (Caddy, Traefik or Nginx) in front of port 8080

---

## First admin user

- **Demo data:** log in at `/admin` with `admin@shop.test` / `password`. Change the email and password straight away (Admin → Customers & staff → edit).
- **Empty store:** create the admin from the command line:

```bash
php artisan tinker --execute="App\Models\User::create(['name'=>'Owner','email'=>'you@example.com','phone'=>'01XXXXXXXXX','password'=>bcrypt('a-strong-password'),'is_admin'=>true]);"
```

Only users with `is_admin = 1` can open `/admin`. Customers can see only their own orders.

## Branding: change it to your shop

Everything below is done in the admin panel. No coding is needed.

| What | Where |
|---|---|
| Shop name, logo, favicon, phone, address, currency | Settings → Store settings |
| Colours, font, corner style, header layout, menus, footer, social links, payment badges | Storefront → Theme, header & footer |
| Home page sections and banners | Storefront → Home page builder |
| Payment methods (COD, bKash, Nagad, bank) and checkout rules | Settings → Store settings → Checkout / Payments |
| Delivery charges | Settings → Shipping zones |
| SEO title, description, Google / Facebook tracking | Settings → SEO & tracking |
| Popups, WhatsApp, Buy Now, trust badges | Marketing → Sales boosters |

**Delete the demo products before you go live:** Catalog → Products → select all → Delete. Then add your own products.

## Go-live checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`
- [ ] SSL active (the `.htaccess` forces HTTPS)
- [ ] Demo admin email and password changed
- [ ] `php artisan optimize` run after every `.env` change
- [ ] Submit `https://your-domain.com/sitemap.xml` in Google Search Console
- [ ] `php artisan test` passes. It uses a separate test database; see `phpunit.xml`.
