# Ticket Events Platform

A comprehensive platform for managing venues, creating events, and selling electronic tickets. Designed with a focus on reliability, scalability, and clean code using modern architectural patterns.

## 🚀 Key Innovations & Architecture

* **Domain-Driven Design (DDD) & CQRS:** Strict separation into Domain, Application, and Infrastructure layers. Utilizes Command and Query buses for request handling.
* **Transactional Outbox:** Guaranteed delivery of domain events (order creation, payment, refund) to the message broker without the risk of database desynchronization.
* **Real-Time Analytics:** Asynchronous event logging via RabbitMQ into ClickHouse (columnar DBMS) for instant reporting.
* **Full-Text Search:** Integration with Elasticsearch 8 for fast and relevant event discovery.
* **Interactive UI/UX:** Yandex Maps integration for venue coordinates, bulk ticket pricing management (Standard, VIP, Premium), and visual "aging" (grayscale) for past events.
* **Real-Time Support Chat:** Built-in WebSocket server based on Amphp for seamless client-admin communication.
* **Payment Gateway:** Integration with Stripe Checkout and webhook processing for payment confirmation (replacing the legacy Mock Bank).
* **Clean Architecture Tests:** Comprehensive code coverage (Unit, Integration, Functional) adhering to strict Clean Code standards (zero visual clutter, DataProviders, isolated test databases).

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | Symfony 7, PHP 8.5 (fpm), Doctrine ORM, Symfony Messenger |
| **Frontend** | React 18 + Vite, TypeScript, TanStack Query, react-hook-form + zod, Tailwind CSS, `@pbe/react-yandex-maps` |
| **Databases** | PostgreSQL 16 (Primary), ClickHouse (Analytics) |
| **Infrastructure** | Elasticsearch 8, RabbitMQ, Redis, Nginx, Docker |
| **WebSockets** | Amphp (Amp) |

## 📁 Project Structure

    app/                  # Backend application (Symfony)
      src/
        Domain/           # Entities, Value Objects, repository interfaces
        Application/      # CQRS commands/queries, DTOs, services, Transactional Outbox
        Infrastructure/   # API controllers, Doctrine/ClickHouse implementations, WebSocket server
    frontend/             # Frontend application (React)
    docker/               # Docker configurations (PHP, Nginx, ClickHouse init)

## 👥 Demo Accounts

After running the database seeders, the following accounts will be available:

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@tickets.by` | `admin1234` |
| Customer | `demo@tickets.by` | `demo1234` |
| Customer | `anna@tickets.by` | `anna1234` |

## 🚀 Quick Start (Docker)

Requirements: Docker Desktop and Node.js 18+.

1. **Launch infrastructure** (nginx, php-fpm, postgres, redis, rabbitmq, elasticsearch, clickhouse, worker):
   ```bash
   docker compose up -d --build
   ```

2. **Apply migrations and seed demo data**:
   ```bash
   docker exec tick_php php bin/console doctrine:migrations:migrate --no-interaction
   docker exec tick_php php bin/console app:seed-demo-data
   ```

3. **Start the WebSocket server** (for the support chat):
   ```bash
   docker exec -d tick_php php bin/console app:chat-server
   ```

4. **Launch the Frontend application**:
   ```bash
   cd frontend
   npm install
   # Ensure FRONTEND_URL and keys for Stripe/Yandex Maps are set in your .env file
   npm run dev
   ```

The Frontend will be available at `http://localhost:5173`, and the Backend API at `http://localhost`.

## 🧪 Testing

The project is fully covered by three levels of tests using PHPUnit. The test database `tickets_back_test` is created automatically and isolated from the main DB.

```bash
# Run all test suites (Unit, Integration, Functional)
docker exec tick_php php bin/phpunit
```

## 📜 Main API Endpoints

* **Public:** `GET /api/events`, `GET /api/events/{id}`, `GET /api/events/search?query=`
* **Auth (JWT):** `POST /api/auth/register`, `POST /api/auth/login`, `POST /api/auth/reset-password`
* **Customer:** `POST /api/orders` (reserve), `POST /api/orders/{id}/pay` (Stripe checkout), `POST /api/chat/room`
* **Admin (ROLE_ADMIN):** `GET /api/admin/analytics` (ClickHouse), `POST /api/venues`, `POST /api/events/{id}/publish`, `GET /api/admin/tickets/{code}`