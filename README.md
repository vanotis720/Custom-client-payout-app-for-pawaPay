# DHru ↔ pawaPay Payment Bridge (Laravel 10)

Production-grade backend for collection and payout processing between DHru Fusion and pawaPay.

## Core Capabilities

- Invoice-based collection initialization from DHru invoice data
- pawaPay collection creation with idempotency
- Webhook-first asynchronous confirmation with queued processing
- Manual status re-check endpoint for user-driven confirmation
- Safe DHru crediting with double-credit protection
- Admin dashboard (Breeze auth + admin role middleware)
- Admin transaction filtering and payout initiation

## High-Level Architecture

- **Controllers**: request orchestration only (thin)
- **Form Requests**: validation and authorization boundaries
- **Services**:
    - `DhruService`: invoice validation + crediting
    - `PawapayService`: collection, payout, status, signature checks
    - `PaymentOrchestrationService`: end-to-end business logic + idempotency
- **Jobs**: `ProcessPawapayWebhookJob` for webhook processing
- **Persistence**:
    - `transactions` table for all collection/payout ledger rows
    - `payouts` table for payout-specific tracking

## Public Flow Endpoints

- `GET /payment/start?invoice_id=XXXX`
- `POST /payment/check-status`
- `POST /webhook/pawapay`

## Admin Endpoints (Session Auth + Admin Role)

- `GET /admin/dashboard`
- `GET /admin/transactions`
- `GET /admin/payouts/create`
- `POST /admin/payouts`

## Environment Configuration

Populate values in `.env`:

- DHru:
    - `DHRU_API_URL`
    - `DHRU_API_USER`
    - `DHRU_API_KEY`
- pawaPay:
    - `PAWAPAY_BASE_URL`
    - `PAWAPAY_API_KEY`
    - `PAWAPAY_WEBHOOK_SECRET`
    - `PAWAPAY_SIGNATURE_HEADER`
- Queue/logging:
    - `QUEUE_CONNECTION`
    - `PAYMENT_LOG_LEVEL`
- Admin bootstrap user:
    - `ADMIN_NAME`
    - `ADMIN_EMAIL`
    - `ADMIN_PASSWORD`

## Deployment Steps

1. Install dependencies:
    - `composer install --no-dev --optimize-autoloader`
    - `npm ci && npm run build`
2. Configure `.env` with production secrets.
3. Generate key if needed: `php artisan key:generate`
4. Run migrations and seed admin user:
    - `php artisan migrate --force`
    - `php artisan db:seed --force`
5. Cache config/routes/views:
    - `php artisan config:cache`
    - `php artisan route:cache`
    - `php artisan view:cache`
6. Run queue workers for webhook processing:
    - `php artisan queue:work --queue=default --tries=3 --timeout=90`
7. Configure scheduler/monitoring and centralize `storage/logs/payments-*.log`.

## Security Controls Included

- Webhook HMAC signature validation
- API credentials loaded from environment/config only
- Idempotency keys for pawaPay create calls
- DB transaction + row locks to prevent double crediting
- Never trust webhook payload status blindly (status is re-queried)
- API interaction logging on dedicated payments channel
# Custom-client-payout-app-for-pawaPay
