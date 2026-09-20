# Host on Plesk (Linux Platinum)

This Laravel ERP runs on **Apache + PHP 8.3+ + MySQL** with **Plesk**. It is not a Vite-only/static site — the domain **document root must be `public/`**.

## 1. Domain & SSL

1. In Plesk → **Websites & Domains** → add your free/primary domain (or subdomain).
2. Enable **SSL/TLS** (Let’s Encrypt free SSL on the plan).
3. Turn on **Permanent SEO-safe 301 redirect from HTTP to HTTPS**.

## 2. PHP

1. Domain → **PHP Settings**.
2. Select **PHP 8.3** (or 8.4). Do **not** use 8.0/8.1/8.2.
3. Enable extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`, `gd` or `imagick`, `zip`, `intl` (recommended), `bcmath`.
4. `public/.user.ini` already sets memory/upload/opcache-friendly values; raise limits in Plesk if needed.

## 3. Upload the app

Recommended layout:

```text
/var/www/vhosts/YOUR-DOMAIN/
  └── app/                    ← Laravel project root (artisan, app/, vendor/, …)
       ├── app/
       ├── bootstrap/
       ├── config/
       ├── database/
       ├── public/            ← set Document Root HERE
       ├── storage/
       ├── vendor/
       ├── .env
       └── artisan
```

1. Upload the project (Git pull over SSH, or ZIP via File Manager).
2. Plesk → **Hosting Settings** → **Document root** = `app/public` (path relative to the subscription home).
3. Do **not** point document root at the project root (exposes `.env`).

Exclude from upload if installing on server: `node_modules/`.  
You can run `composer install` on the server, or upload `vendor/` from a matching PHP 8.3 machine.

## 4. Environment

```bash
cp deploy/plesk/env.production.example .env
# edit .env — set APP_KEY, APP_URL, DB_*, JWT_SECRET, mail
php artisan key:generate   # if APP_KEY empty
```

- **Database:** use your **Aiven** MySQL (already wired) **or** create one of the plan’s **10 MySQL databases** in Plesk and put those credentials in `.env`.
- `APP_URL=https://YOUR-DOMAIN.COM`
- `APP_DEBUG=false`
- `FORCE_HTTPS=true`
- `SESSION_SECURE_COOKIE=true`

## 5. Install & optimize (SSH)

```bash
cd /var/www/vhosts/YOUR-DOMAIN/app
bash deploy/plesk/post-deploy.sh
```

Or manually:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build    # or upload public/build from your PC
php artisan migrate --force
php artisan storage:link
php artisan optimize
chmod -R ug+rwx storage bootstrap/cache
```

Local asset build before upload (Windows):

```powershell
npm run build
# then upload the public/build folder
```

## 6. Cron (Plesk Scheduled Tasks)

Add a task, **every minute**:

```bash
cd /var/www/vhosts/YOUR-DOMAIN/app && /opt/plesk/php/8.3/bin/php artisan schedule:run >> /dev/null 2>&1
```

(Adjust the `php` path to whatever Plesk shows under PHP Settings.)

## 7. Email (optional)

Use a Plesk mailbox (`noreply@YOUR-DOMAIN.COM`) with:

```env
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=noreply@YOUR-DOMAIN.COM
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@YOUR-DOMAIN.COM
```

## 8. Quick checks

| Check | Expect |
|--------|--------|
| `https://YOUR-DOMAIN.COM/up` | Laravel health OK |
| `https://YOUR-DOMAIN.COM/login` | SNS Furniture login |
| Browser padlock | Valid Let’s Encrypt cert |
| `APP_DEBUG` | `false` |

## 9. Subdomains

Unlimited subdomains: create e.g. `erp.YOUR-DOMAIN.COM`, same steps, document root → that vhost’s `public/`, separate or shared `.env` `APP_URL`.

## Security notes already in the app

- Security headers + HSTS, CSP, rate-limited login
- Encrypted DB sessions, HTTPS forced in production
- `robots.txt` disallows indexing
- Keep `.env` **outside** the web root (only `public/` is served)
