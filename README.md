# Tocaan Order & Payment API

A RESTful API built with Laravel 13 for managing orders and processing payments using JWT authentication and a Strategy Pattern for payment gateways.

## Tech Stack

- PHP 8.3+
- Laravel 13.x
- SQLite (development) / MySQL or PostgreSQL (production)
- JWT Authentication (tymon/jwt-auth 2.0)
- PHPUnit 12.x

## Architecture

The project follows Clean Architecture with layered separation:

```
Controller → Service → Repository → Model
```

- **Controllers** — Thin HTTP layer; delegates to services.
- **Services** — Business logic and orchestration.
- **Repositories** — Data access abstraction via interfaces.
- **DTOs** — Data transfer between layers (readonly classes).
- **Enums** — Type-safe status and method constants.
- **Strategy Pattern** — Payment gateways resolved at runtime via config.

## Setup Instructions

### Prerequisites

- PHP 8.3 or higher
- Composer 2.x
- Node.js & npm (for asset building, optional for API-only usage)

### Installation

```bash
# Clone the repository
git clone <repository-url>
cd tocaan-order-payment-api

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Run migrations
php artisan migrate

# (Optional) Seed sample data
php artisan db:seed
```

### Running the Application

```bash
# Start the development server
php artisan serve

# The API will be available at http://localhost:8000/api
```

### Running Tests

```bash
php artisan test
```

## Authentication

All protected endpoints require a JWT token in the `Authorization` header:

```
Authorization: Bearer <your-jwt-token>
```

### Auth Endpoints

| Method | Endpoint            | Description         | Auth |
|--------|---------------------|---------------------|------|
| POST   | `/api/auth/register`| Register a new user | No   |
| POST   | `/api/auth/login`   | Login               | No   |
| POST   | `/api/auth/logout`  | Logout              | Yes  |
| GET    | `/api/auth/me`      | Get current user    | Yes  |

## API Endpoints

### Orders

| Method | Endpoint                     | Description              | Auth |
|--------|------------------------------|--------------------------|------|
| GET    | `/api/orders`                | List orders (paginated)  | Yes  |
| POST   | `/api/orders`                | Create a new order       | Yes  |
| GET    | `/api/orders/{id}`           | View a single order      | Yes  |
| PUT    | `/api/orders/{id}`           | Update order items       | Yes  |
| DELETE | `/api/orders/{id}`           | Delete an order          | Yes  |
| PATCH  | `/api/orders/{id}/status`    | Update order status      | Yes  |

#### Query Parameters (GET /api/orders)

- `status` — Filter by status: `pending`, `confirmed`, `cancelled`
- `per_page` — Items per page (default: 15)
- `page` — Page number

### Payments

| Method | Endpoint                      | Description                 | Auth |
|--------|-------------------------------|-----------------------------|------|
| POST   | `/api/payments`               | Process a payment           | Yes  |
| GET    | `/api/orders/{id}/payment`    | View payment for an order   | Yes  |

## Business Rules

### Orders

- New orders start with `pending` status
- Valid transitions: `pending → confirmed`, `pending → cancelled`
- Confirmed orders cannot be cancelled
- Cancelled orders cannot be confirmed
- Only pending orders can be modified or deleted
- Orders with payments cannot be deleted
- Order total is auto-calculated from items (quantity × price)

### Payments

- Payments can only be processed for `confirmed` orders
- Payment amount must match order total exactly
- Each order can only have one successful payment
- Supported methods: `credit_card`, `paypal`, `cash`

## Response Format

### Success Response

```json
{
    "message": "Order created successfully.",
    "data": {
        "id": 1,
        "user_id": 1,
        "status": "pending",
        "total": "45.50",
        "items": [...],
        "created_at": "2026-06-24T16:00:00+00:00",
        "updated_at": "2026-06-24T16:00:00+00:00"
    }
}
```

### Error Response

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field": ["Error message."]
    }
}
```

### Paginated Response

```json
{
    "data": [...],
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    },
    "meta": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 15,
        "total": 42
    }
}
```

## Adding a New Payment Gateway

The payment system uses the Strategy Pattern. To add a new gateway:

### Step 1: Create the Gateway Class

```php
<?php

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGatewayInterface;

class StripeGateway implements PaymentGatewayInterface
{
    public function charge(float $amount, array $details = []): array
    {
        // Integrate with Stripe SDK here
        return [
            'success' => true,
            'transaction_id' => 'stripe_' . uniqid(),
            'message' => 'Stripe payment processed.',
        ];
    }

    public function getName(): string
    {
        return 'stripe';
    }
}
```

### Step 2: Register in Config

Add the new gateway to `config/payment_gateways.php`:

```php
'gateways' => [
    'credit_card' => CreditCardGateway::class,
    'paypal' => PaypalGateway::class,
    'cash' => CashGateway::class,
    'stripe' => \App\Payments\Gateways\StripeGateway::class, // ← Add here
],
```

That's it. No changes to existing code required (Open/Closed Principle).

## Postman Collection Examples

### Register

```
POST /api/auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

### Login

```
POST /api/auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```

### Create Order

```
POST /api/orders
Authorization: Bearer <token>
Content-Type: application/json

{
    "items": [
        {
            "product_name": "Wireless Keyboard",
            "quantity": 2,
            "price": 49.99
        },
        {
            "product_name": "USB-C Hub",
            "quantity": 1,
            "price": 35.00
        }
    ]
}
```

### Update Order Items

```
PUT /api/orders/1
Authorization: Bearer <token>
Content-Type: application/json

{
    "items": [
        {
            "product_name": "Wireless Keyboard",
            "quantity": 3,
            "price": 49.99
        }
    ]
}
```

### Confirm Order

```
PATCH /api/orders/1/status
Authorization: Bearer <token>
Content-Type: application/json

{
    "status": "confirmed"
}
```

### Process Payment

```
POST /api/payments
Authorization: Bearer <token>
Content-Type: application/json

{
    "order_id": 1,
    "amount": 134.98,
    "method": "credit_card"
}
```

### List Orders with Filter

```
GET /api/orders?status=pending&per_page=10&page=1
Authorization: Bearer <token>
```

### View Order Payment

```
GET /api/orders/1/payment
Authorization: Bearer <token>
```

## Assumptions

1. **Single payment per order** — Each order can have at most one successful payment. Failed payments are recorded but allow retry.
2. **Simulated gateways** — Payment gateways simulate success for development. In production, integrate with real SDKs (Stripe, PayPal, etc.).
3. **User-scoped orders** — Users can only access their own orders. There is no admin role yet.
4. **No partial payments** — Payment amount must match the full order total.
5. **SQLite for development** — The project uses SQLite for local development and testing. Configure MySQL/PostgreSQL in `.env` for production.
6. **No order editing after confirmation** — Once confirmed, order items cannot be modified.
7. **Cascading deletes** — Deleting an order removes its items and payments (database-level cascade).
8. **Rate limiting** — Auth endpoints are rate-limited to 5 requests per minute to prevent brute force.

## Project Structure

```
app/
├── DTOs/                    # Data Transfer Objects
├── Enums/                   # OrderStatus, PaymentStatus, PaymentMethod
├── Http/
│   ├── Controllers/         # Thin controllers
│   ├── Requests/            # Form Request validation
│   └── Resources/           # API Resource transformations
├── Models/                  # Eloquent models with relationships
├── Payments/
│   ├── Contracts/           # PaymentGatewayInterface
│   └── Gateways/            # CreditCard, PayPal, Cash gateways
├── Repositories/
│   ├── Contracts/           # Repository interfaces
│   └── Eloquent/            # Eloquent implementations
├── Services/                # Business logic layer
└── Providers/               # Service & repository bindings
```

## License

MIT
