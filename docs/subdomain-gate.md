# Optional: private subdomain gate

Use this when you run a second app on a subdomain (for example `erp.your-domain.com`) and want nobody to open it directly. Staff go to `https://your-domain.com/dhaka`, and the store sets a cookie and redirects them to the subdomain. The subdomain's `.htaccess` refuses every request that doesn't carry the cookie.

The gate is **off by default**: `/dhaka` returns 404 until you configure it.

## 1. Main store – `.env`

```dotenv
ERP_URL=https://erp.your-domain.com
ERP_GATE_SECRET=        # generate: openssl rand -hex 32
ERP_COOKIE_DOMAIN=.your-domain.com
ERP_GATE_MINUTES=600    # how long access lasts
```

Then run `php artisan optimize`.

The code:

- `app/Http/Controllers/ErpGateController.php`
- the route in `routes/web.php`
- the cookie is excluded from encryption in `bootstrap/app.php`

## 2. Subdomain – top of its `.htaccess`, right after `RewriteEngine On`

```apache
RewriteCond %{HTTP_COOKIE} !(^|;\s*)gnw_erp_gate=YOUR_SECRET(;|$)
RewriteRule !^\.well-known/ - [F]
```

## Limits

- `/dhaka` is a public URL. Anyone who knows it gets through, so keep the subdomain app's own login strong.
- Requests that don't come from a browser (APIs, webhooks, mobile apps) won't have the cookie. Exclude those paths in the rule.
