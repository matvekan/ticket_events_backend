# Ticket Events Backend

Educational project: online ticket sales for events (Minsk venues) with Symfony 7 / PHP 8.5 backend, React (Vite) frontend, and infrastructure services.

## Stack

| Layer | Technology |
|---|---|
| Backend | Symfony 7, PHP 8.5 (fpm), Doctrine ORM + migrations, Messenger (RabbitMQ), JWT (Lexik) |
| Frontend | React 18 + Vite, TanStack Query, react-hook-form + zod, Tailwind, `qrcode.react` |
| Database | PostgreSQL 16 |
| Analytics | ClickHouse (event tables) |
| Search | Elasticsearch 8 |
| Queues | RabbitMQ |
| Cache | Redis |
| Web Server | Nginx (port 80) |

## Structure

```
app/                  # Symfony application
  src/
    Domain/           # entities, value objects, repository interfaces
    Application/      # commands/handlers, services, queries/query handlers
    Infrastructure/   # API controllers, Doctrine repositories, ClickHouse, console
  migrations/         # DB migrations
  tests/              # integration tests (PHPUnit)
frontend/             # React application
docker/               # PHP Dockerfile, nginx config, ClickHouse init.sql
scripts/              # E2E checks (PowerShell, Windows)
docker-compose.yaml   # infrastructure orchestration
```

## Demo Accounts (after seeding)

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@tickets.by` | `admin1234` |
| Customer | `demo@tickets.by` | `demo1234` |
| Customer | `anna@tickets.by` | `anna1234` |

## Cold Start

Requirements: Docker Desktop, Node.js 18+, PowerShell (for E2E scripts).

```powershell
# 1. Infrastructure (nginx, php-fpm, postgres, redis, rabbitmq, elasticsearch, clickhouse, worker)
docker compose up -d --build

# 2. DB schema + demo data (vendor is baked into the image via multi-stage build)
docker exec tick_php php bin/console doctrine:migrations:migrate --no-interaction
docker exec tick_php php bin/console app:seed-demo-data

# 3. Frontend
cd frontend
npm install
npm run dev        # http://localhost:5173

# 4. Backend available at http://localhost (nginx, port 80)
```

Seeds create 6 real Minsk venues (Minsk-Arena, Sports Palace, Prime Hall, Chizhovka-Arena, MAZ Palace, Belarusian State Philharmonic) with 64 seats, 8 events for Aug–Dec 2026 (7 published, 1 draft), and demo orders in various statuses. The command is idempotent — repeated runs do not duplicate data.

## Mock Bank Payment Flow

1. User reserves seats → order `pending`.
2. Clicks "Pay" → `POST /api/orders/{id}/pay` returns `paymentUrl`.
3. Frontend redirects to mock bank `GET /mock-bank/{paymentId}` (payment form with prefilled card `4242 4242 4242 4242`).
4. `POST /mock-bank/{paymentId}/charge` — payment (≈1 sec, order becomes `paid`, tickets issued with QR codes).
5. `POST /mock-bank/{paymentId}/decline` — decline: order stays `pending`, retry reuses same payment (restart).

After payment, tickets with QR codes are available on the order page. Ticket verification by code — in admin ("Ticket Verification", `GET /api/admin/tickets/{code}`), returns statuses/reasons in JSON (`valid`, `reason`, ticket data).

## Events & Analytics

Messenger worker (`tick_worker`) consumes RabbitMQ queue and processes domain events: reservation, payment, cancellation, refund. Each event is also written to ClickHouse tables `seat_reservations`, `order_payments`, `order_cancellations`, `order_refunds` (schemaless MergeTree: `order_id`, `user_id`, `amount`, `timestamp`).

Admin "Analytics" (`GET /api/admin/analytics`) aggregates these tables: total revenue/refunds/cancellations/reservations, 14-day trend, top buyers, recent payments. Returns `503` with explanation if ClickHouse is unavailable.

## Tests

```powershell
# PHPUnit (separate test DB tickets_back_test, created automatically from migrations)
docker exec tick_php php bin/phpunit

# E2E scenario (register → venue → event → reserve → mock bank payment → refund)
powershell -ExecutionPolicy Bypass -File scripts\e2e.ps1        # expect ALL_E2E_OK

# Full payment scenario (checkout page, decline, payment restart, charge)
powershell -ExecutionPolicy Bypass -File scripts\bank_flow.ps1  # expect BANK_FLOW_OK
```

## Main API

Public: `GET /api/events`, `GET /api/events/{id}`, `GET /api/events/{id}/seats`, `GET /api/events/search?query=`, `POST /api/auth/register`, `POST /api/auth/login`.

User (JWT): `GET/POST /api/orders/*` (reserve, my orders, pay, cancel), `GET /api/venues/*`.

Admin (ROLE_ADMIN): `GET /api/admin/events`, `POST /api/admin/orders/{id}/refund`, `GET /api/admin/analytics`, `GET /api/admin/tickets/{code}`, plus shared venue/event creation endpoints.

OpenAPI docs: `GET /api/doc`.