# Inventory Management API

A RESTful API built with Laravel and JWT authentication for managing inventory, sales, purchases, expenses, and more.

## Requirements

- PHP >= 8.2
- Composer
- MySQL / SQLite
- Laravel 11.x

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Authentication

All protected routes require a Bearer JWT token in the `Authorization` header.

```
Authorization: Bearer <token>
```

### Auth Endpoints (Public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/admin/register` | Register a new admin |
| POST | `/api/admin/login` | Login and get JWT token |
| POST | `/api/admin/forgot-password` | Send password reset email |
| POST | `/api/admin/validate-reset-token` | Validate reset token |
| POST | `/api/admin/reset-password` | Reset password |

### Auth Endpoints (Protected)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/admin/logout` | Logout |
| GET | `/api/admin/profile` | Get profile |
| POST | `/api/admin/update-profile` | Update profile |

---

## API Endpoints

All endpoints below are prefixed with `/api/admin` and require JWT authentication.

### Users

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/users` | Get all users |
| POST | `/create-user` | Create user |
| PUT | `/update-user/{id}` | Update user |
| DELETE | `/delete-user/{id}` | Delete user |

### Customers

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/customers` | Get all customers |
| GET | `/view-customers/{id}` | View customer |
| POST | `/create-customer` | Create customer |
| PUT | `/update-customer/{id}` | Update customer |
| DELETE | `/delete-customer/{id}` | Delete customer |

### Suppliers

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/suppliers` | Get all suppliers |
| GET | `/view-suppliers/{id}` | View supplier |
| POST | `/create-supplier` | Create supplier |
| PUT | `/update-supplier/{id}` | Update supplier |
| DELETE | `/delete-supplier/{id}` | Delete supplier |

### Stores

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/stores` | Get all stores |
| GET | `/view-stores/{id}` | View store |
| POST | `/create-store` | Create store |
| PUT | `/update-store/{id}` | Update store |
| DELETE | `/delete-store/{id}` | Delete store |

### Products

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/products` | Get all products |
| GET | `/view-products/{id}` | View product |
| POST | `/create-product` | Create product |
| POST | `/update-product/{id}` | Update product |
| DELETE | `/delete-product/{id}` | Delete product |

### Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/categories` | Get all categories |
| GET | `/view-categories/{id}` | View category |
| POST | `/create-category` | Create category |
| PUT | `/update-category/{id}` | Update category |
| DELETE | `/delete-category/{id}` | Delete category |

### Brands

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/brands` | Get all brands |
| GET | `/view-brands/{id}` | View brand |
| POST | `/create-brand` | Create brand |
| POST | `/update-brand/{id}` | Update brand |
| DELETE | `/delete-brand/{id}` | Delete brand |

### Purchases

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/purchases` | Get all purchases |
| GET | `/view-purchases/{id}` | View purchase |
| POST | `/create-purchase` | Create purchase |
| PUT | `/update-purchase/{id}` | Update purchase |
| DELETE | `/delete-purchase/{id}` | Delete purchase |

### Purchase Returns

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/purchase-returns` | Get all purchase returns |
| GET | `/view-purchase-returns/{id}` | View purchase return |
| POST | `/create-purchase-return` | Create purchase return |
| PUT | `/update-purchase-return/{id}` | Update purchase return |
| DELETE | `/delete-purchase-return/{id}` | Delete purchase return |

### Sales

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/sales` | Get all sales |
| GET | `/view-sales/{id}` | View sale |
| POST | `/create-sale` | Create sale |
| PUT | `/update-sale/{id}` | Update sale |
| DELETE | `/delete-sale/{id}` | Delete sale |

### Sale Returns

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/sale-returns` | Get all sale returns |
| GET | `/view-sale-returns/{id}` | View sale return |
| POST | `/create-sale-return` | Create sale return |
| PUT | `/update-sale-return/{id}` | Update sale return |
| DELETE | `/delete-sale-return/{id}` | Delete sale return |

### Expense Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/expense-categories` | Get all expense categories |
| GET | `/view-expense-categories/{id}` | View expense category |
| POST | `/create-expense-category` | Create expense category |
| PUT | `/update-expense-category/{id}` | Update expense category |
| DELETE | `/delete-expense-category/{id}` | Delete expense category |

### Expenses

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/expenses` | Get all expenses |
| GET | `/view-expenses/{id}` | View expense |
| POST | `/create-expense` | Create expense |
| POST | `/update-expense/{id}` | Update expense |
| DELETE | `/delete-expense/{id}` | Delete expense |

### Stock Adjustments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/adjustments` | Get all adjustments |
| GET | `/view-adjustments/{id}` | View adjustment |
| POST | `/create-adjustment` | Create adjustment |
| PUT | `/update-adjustment/{id}` | Update adjustment |
| DELETE | `/delete-adjustment/{id}` | Delete adjustment |

### Reports

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/reports/sales` | Sales report |
| GET | `/reports/purchases` | Purchase report |
| GET | `/reports/inventory` | Inventory report |
| GET | `/reports/customers` | Customer report |
| GET | `/reports/suppliers` | Supplier report |

### Activity Logs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/activity-logs` | Get activity logs |
| DELETE | `/activity-logs/clear` | Clear all activity logs |

### Settings

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/settings` | Get settings |
| PUT | `/settings` | Update settings |
| POST | `/settings/test-email` | Test email connection |

### Dashboard

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/dashboard` | Get dashboard data |

### Notifications

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/notifications` | Get notifications |
| POST | `/notifications/mark-read` | Mark notification as read |
| POST | `/notifications/mark-all-read` | Mark all notifications as read |

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
