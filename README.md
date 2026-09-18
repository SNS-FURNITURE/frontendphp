# SNS Furniture — public site + Laravel ERP

Monorepo serving:

| URL | Purpose |
|-----|---------|
| `/` | Public marketing website (catalog, about, locations) |
| `/login` | ERP staff login (not linked from public nav/footer) |
| `/workspace` | ERP entry — redirects to the user’s home module |

Planned public domain: **snsfurniture.et** (set `APP_URL` / `PUBLIC_SITE_DOMAIN` when live).

Staff portal maps onto the **existing MySQL** database `sns_erp_db`.  
Do **not** run `php artisan migrate` against this database.

## Stack

- Laravel + Blade + Alpine.js (public marketing + ERP)
- Session auth for ERP UI; JWT (`sns_token`) for `/api/v1`
- Catalog products live in `config/site.php` (display-only, no cart/payment)
- MySQL via Eloquent (existing tables only)

## Setup

```bash
cp .env.example .env
php artisan key:generate
# Edit .env: DB_* and JWT_SECRET (match sns-erp-backend)
php artisan storage:link
php artisan serve --port=8000
```

- Public site: `http://localhost:8000/`
- ERP login: `http://localhost:8000/login`

## Update products (public catalog)

Edit `config/site.php` → `products` array (slug, name, category, price, images, etc.).  
Clear config cache after deploy: `php artisan config:clear`.

## Deploy notes

1. Point `snsfurniture.et` (or current host) at this Laravel app.
2. Set `APP_URL=https://snsfurniture.et` and enable HTTPS (Let’s Encrypt / host).
3. One app serves both `/` and `/login` — no reverse-proxy split required.
4. Ensure `robots.txt` and `sitemap.xml` routes are publicly reachable.

## Launch roles / demo logins

See ERP roles in the table below. Demo password: `password123`.

| Role | Notes |
|------|------|
| finance | Invoices create/pay/edit |
| admin | Observer on finance |
| hr | Attendance marking |
| … | Full RBAC in app |

Demo users: `finance@sns.com`, `admin@sns.com`, `hr@sns.com`, etc.

## Features

### Public site
- Home, products (filters), product detail, about, locations/contact
- Inquiry contact form (no e-commerce checkout)
- SEO: meta tags, sitemap.xml, robots.txt

### ERP
- Workspace after login; invoices, HR, inventory, payroll, and more
- Idle logout after 30 minutes
