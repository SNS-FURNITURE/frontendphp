# SNS Furniture — Laravel invoice system

Staff portal for SNS Furniture invoices. Maps onto the **existing MySQL** database `sns_erp_db`.

Do **not** run `php artisan migrate` against this database.

## Stack

- Laravel + Blade + Alpine.js
- Session auth for staff UI; JWT (`sns_token`) for `/api/v1`
- DomPDF for invoice PDF download
- MySQL via Eloquent (existing tables only)

## Setup

```bash
cp .env.example .env
php artisan key:generate
# Edit .env: DB_* and JWT_SECRET (match sns-erp-backend)
php artisan serve --port=8000
```

Open `http://localhost:8000/login`

## Launch roles

`admin`, `company_manager`, `finance`, `marketing_manager`, `sales_supervisor`, `sales`, `operations_customer`, `operations_factory`

| Role | View | Create / pay | Edit |
|------|------|--------------|------|
| finance | yes | yes | yes |
| sales_supervisor / sales | yes | yes | no |
| operations_customer | yes | no | no |
| operations_factory | yes | no | no |
| marketing_manager | yes | yes | no |
| admin | yes | no (observer) | no |
| company_manager | yes | no | no |

## Demo logins (`password123`)

- `finance@sns.com` / `@finance`
- `admin@sns.com`
- `manager@sns.com`
- `mktmanager@sns.com`
- `advisor@sns.com` (sales supervisor)
- `sales@sns.com`
- `opscustomer@sns.com`
- `opsfactory@sns.com`

## Features

- Split staff-portal login
- Dark workspace: Invoices, Payments, My Profile
- A4 Word-like invoice editor (create / edit)
- Print (HTML) and Download PDF (same SNS document layout)
- Payments that mark invoices paid when sum covers amount
- Idle logout after 30 minutes

## API

`/api/v1/auth/*`, `/api/v1/profile`, `/api/v1/invoices/*`, `/api/v1/payments`, `/api/v1/documents/*`

JSON envelope: `{ "success": true, "data": ... }` / `{ "success": false, "error": { "code", "message" } }`
