# BizDesk API

Laravel 13 backend for the **BizDesk** Flutter app: multi-business REST API (Sanctum tokens) + a web dashboard.
Money is stored in **minor units** (kobo/cents) as integers, same as the app.

## Run it
```bash
cd ~/Developer/bizdesk-api
php artisan migrate:fresh --seed
php artisan serve        # dashboard: http://127.0.0.1:8000  (owner@bizdesk.test / password)
```

## API (all under /api/v1, Bearer token except register/login)
- `POST auth/register`, `POST auth/login`, `GET auth/me`, `POST auth/logout`
- `products` (CRUD, `?search=&category=&low_stock=1`), `POST products/{id}/restock {qty}`
- `customers` (CRUD, includes total spent)
- `sales`: `POST {items:[{product_id,qty}], customer_id?, discount?, payment_method?}`: transactional, row-locked; `DELETE` voids and restocks
- `expenses` (index/store/destroy)
- `GET reports/summary?period=today|week|month|all`

Tests: `php artisan test`
