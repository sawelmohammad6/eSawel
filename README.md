# Varsity Market

A Laravel multi-vendor e-commerce project built from your PDF requirement and styled to feel close to the Govaly-inspired reference screenshots.

## Stack

- Laravel 12
- Blade + HTML
- Tailwind CSS 4
- Vanilla JavaScript
- PHP 8.2+
- MySQL-ready schema

## What Is Included

- Customer auth with register, login, logout, and password reset
- Govaly-style storefront with hero search, category drawer, product cards, brand section, and product detail page
- Product browsing with search, category/brand filters, sorting, pagination, and suggestions
- Cart, wishlist, compare list, checkout, orders, return request, and account dashboard
- Seller dashboard with product management, order status updates, and payout requests
- Admin dashboard with CRUD-style management for:
  - products
  - categories
  - brands
  - banners
  - coupons
  - sellers
  - users
  - orders
  - reports
- Seeded demo data so the UI is populated immediately

## Demo Accounts

- Admin: `admin@example.com` / `password`
- Seller: `seller@example.com` / `password`
- Customer: `customer@example.com` / `password`

## Verified Commands

```bash
php artisan migrate:fresh --seed
php artisan route:list
php artisan test
npm install
npm run build
```

## Run Locally

```bash
php artisan serve
```

Then open `http://127.0.0.1:8000`.

## MySQL Note

The project structure, migrations, and data model are MySQL-ready, but this workspace is still using SQLite in `.env` for verification because the local MySQL service on port `3306` is not accepting the expected `root` / empty-password login.

To switch to MySQL, update `.env` like this after confirming your local MySQL credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=varsity_market
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

Then run:

```bash
php artisan migrate:fresh --seed
```
