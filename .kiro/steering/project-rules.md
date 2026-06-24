# Tocaan Order & Payment API — Project Rules

## Architecture Rules

- Follow Clean Architecture with layered separation: Controller → Service → Repository → Model
- Controllers MUST be thin — delegate all business logic to Service classes
- Services contain business logic and orchestration only
- Repositories abstract all data access — services NEVER call Eloquent directly
- Repositories depend on interfaces (contracts), bound via `RepositoryServiceProvider`
- Use DTOs to pass data between layers — no raw arrays crossing boundaries
- Every DTO must have `fromArray()` and `toArray()` methods
- Models define relationships, casts, and fillable — no business logic

## SOLID Principles

- Single Responsibility: One class = one job. No god classes.
- Open/Closed: New payment gateways are added via config + new class — zero changes to existing code
- Liskov Substitution: All gateway implementations must satisfy `PaymentGatewayInterface` fully
- Interface Segregation: Keep interfaces focused — no fat contracts
- Dependency Inversion: Services depend on interfaces, not concrete implementations

## Code Standards

- Follow PSR-12 strictly
- Use PHP 8.2+ features: readonly classes, named arguments, enums, match expressions
- All methods must have explicit return types
- Use PHPDoc `@return` with typed array shapes where applicable
- No `mixed` types unless absolutely necessary
- Use `readonly class` for all DTOs
- Use backed enums for status fields (OrderStatus, PaymentStatus, PaymentMethod)

## Validation Rules

- All validation lives in Form Request classes — never in controllers or services
- Controllers call `$request->validated()` only
- Use array syntax for rules: `['required', 'string', 'max:255']`
- Custom business rule validation belongs in Service layer (not Form Requests)

## API Response Rules

- All responses go through API Resource classes — no raw model serialization
- Consistent response envelope: `{ "message": "...", "data": {...} }`
- Error responses: `{ "message": "...", "errors": {...} }`
- Use HTTP status codes correctly: 200, 201, 204, 401, 403, 404, 422, 500
- Paginated responses use Laravel's built-in pagination structure

## Security Rules

- JWT authentication via `tymon/jwt-auth`
- All protected routes use `auth:api` middleware
- Rate limiting on auth endpoints: `throttle:5,1`
- Never expose sensitive data (passwords, tokens) in responses
- Validate and sanitize all input via Form Requests
- Use parameterized queries (Eloquent handles this)

## Business Rules

### Orders
- Order statuses: `pending`, `confirmed`, `cancelled`
- Valid transitions: `pending → confirmed`, `pending → cancelled`
- A confirmed order CANNOT be cancelled
- A cancelled order CANNOT be confirmed
- Orders with payments CANNOT be deleted
- Order total is auto-calculated from sum of item subtotals
- Each item subtotal = quantity × price
- Orders can be filtered by status via query parameter
- List endpoints MUST be paginated (default: 15 per page)

### Payments
- Payment statuses: `pending`, `successful`, `failed`
- Payment methods: `credit_card`, `paypal`, `cash` (extensible via config)
- Payment can ONLY be processed for `confirmed` orders
- Pending or cancelled orders CANNOT be paid
- Payment amount must match order total
- Each order can only have ONE successful payment
- New gateways are added in `config/payments.php` + one new gateway class

## Payment Strategy Pattern

- All gateways implement `PaymentGatewayInterface`
- `PaymentGatewayFactory` resolves gateway class from `config/payments.php`
- Controllers and services reference only the interface — never concrete gateways
- Gateway config format: `'method_name' => GatewayClass::class`

## Testing Rules

- Feature tests for all API endpoints (happy + unhappy paths)
- Unit tests for all service classes
- Unit tests for payment gateway strategy
- Use `RefreshDatabase` trait in all tests
- Use factories for test data
- Test validation errors return 422 with correct error keys
- Test auth guards return 401 for unauthenticated requests
- Test business rules are enforced (status transitions, payment guards)

## File Naming Conventions

- Controllers: `{Resource}Controller.php`
- Services: `{Resource}Service.php`
- Repositories: `{Resource}Repository.php` / `{Resource}RepositoryInterface.php`
- DTOs: `{Action}{Resource}DTO.php` (e.g., `RegisterUserDTO`, `StoreOrderDTO`)
- Form Requests: `{Action}{Resource}Request.php` (e.g., `StoreOrderRequest`)
- Resources: `{Resource}Resource.php` / `{Resource}Collection.php`
- Enums: `{Resource}Status.php` / `{Resource}Method.php`
- Gateways: `{Name}Gateway.php`

## Directory Structure

```
app/
├── DTOs/
├── Enums/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Payments/
│   ├── Contracts/
│   └── Gateways/
├── Repositories/
│   ├── Contracts/
│   └── Eloquent/
├── Services/
└── Providers/
```

## Git & Workflow

- Do not commit `.env` files
- One feature per branch
- Run `php artisan test` before every commit — all tests must pass
- Follow conventional commit messages
