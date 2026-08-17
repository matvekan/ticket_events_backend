# Билетная платформа (ticket_events_backend)

Учебный проект: онлайн-продажа билетов на события (площадки Минска) с бэкендом на Symfony 7 / PHP 8.5, фронтендом на React (Vite) и набором инфраструктурных сервисов.

## Стек

| Слой | Технология |
|---|---|
| Backend | Symfony 7, PHP 8.5 (fpm), Doctrine ORM + миграции, Messenger (RabbitMQ), JWT (Lexik) |
| Frontend | React 18 + Vite, TanStack Query, react-hook-form + zod, Tailwind, `qrcode.react` |
| БД | PostgreSQL 16 |
| Аналитика | ClickHouse (событийные таблицы) |
| Поиск | Elasticsearch 8 |
| Очереди | RabbitMQ |
| Кэш | Redis |
| Веб-сервер | Nginx (порт 80) |

## Структура

```
app/                  # Symfony-приложение
  src/
    Domain/           # сущности, value-object'ы, интерфейсы репозиториев
    Application/      # команды/хендлеры, сервисы, query/query-handler'ы
    Infrastructure/   # контроллеры API, репозитории Doctrine, ClickHouse, console
  migrations/         # миграции БД
  tests/              # интеграционные тесты (PHPUnit)
frontend/             # React-приложение
docker/               # Dockerfile PHP, конфиг nginx, init.sql ClickHouse
scripts/              # E2E-проверки (PowerShell, Windows)
docker-compose.yaml   # подъём всей инфраструктуры
```

## Демо-аккаунты (после сида)

| Роль | Email | Пароль |
|---|---|---|
| Администратор | `admin@tickets.by` | `admin1234` |
| Покупатель | `demo@tickets.by` | `demo1234` |
| Покупатель | `anna@tickets.by` | `anna1234` |

## Холодный старт

Требуется: Docker Desktop, Node.js 18+, PowerShell (для E2E-скриптов).

```powershell
# 1. Инфраструктура (nginx, php-fpm, postgres, redis, rabbitmq, elasticsearch, clickhouse, worker)
docker compose up -d --build

# 2. Зависимости PHP (первый раз)
docker exec tick_php composer install

# 3. Ключи JWT (файлы config/jwt/*.pem в .gitignore)
docker exec tick_php php bin/console lexik:jwt:generate-keypair

# 4. Схема БД + демо-данные
docker exec tick_php php bin/console doctrine:migrations:migrate --no-interaction
docker exec tick_php php bin/console app:seed-demo-data

# 5. Фронтенд
cd frontend
npm install
npm run dev        # http://localhost:5173

# 6. Бэкенд доступен на http://localhost (nginx, порт 80)
```

Сиды создают 6 реальных площадок Минска (Минск-Арена, Дворец Спорта, Prime Hall, Чижовка-Арена, ДК МАЗ, Белгосфилармония) с 64 местами, 8 событий на авг–дек 2026 (7 опубликованы, 1 черновик) и демо-заказы в разных статусах. Команда идемпотентна — повторный запуск ничего не дублирует.

## Оплата через мок-банк

1. Пользователь бронирует места → заказ `pending`.
2. Жмёт «Оплатить» → `POST /api/orders/{id}/pay` возвращает `paymentUrl`.
3. Фронтенд переходит на страницу банка `GET /mock-bank/{paymentId}` (платёжная форма с предзаполненной картой `4242 4242 4242 4242`).
4. `POST /mock-bank/{paymentId}/charge` — оплата (≈1 сек, заказ становится `paid`, билеты выпускаются с QR-кодами).
5. `POST /mock-bank/{paymentId}/decline` — отклонение: заказ остаётся `pending`, повторная оплата переиспользует тот же платёж (restart).

После оплаты билеты с QR-кодом доступны на странице заказа. Проверка билета по коду — в админке («Проверка билетов», `GET /api/admin/tickets/{code}`), при этом статусы/причины возвращаются в JSON (`valid`, `reason`, данные билета).

## События и аналитика

Messenger-воркер (`tick_worker`) потребляет очередь RabbitMQ и обрабатывает доменные события: бронирование, оплата, отмена, возврат. Каждое событие дополнительно пишется в ClickHouse в таблицы `seat_reservations`, `order_payments`, `order_cancellations`, `order_refunds` (schemaless-схема MergeTree: `order_id`, `user_id`, `amount`, `timestamp`).

Админка «Аналитика» (`GET /api/admin/analytics`) агрегирует эти таблицы: суммарная выручка/возвраты/отмены/бронирования, динамика за 14 дней, топ покупателей, последние платежи. Если ClickHouse недоступен — эндпоинт вернёт `503` с пояснением.

## Тесты

```powershell
# PHPUnit (отдельная тестовая БД tickets_back_test, создаётся автоматически из миграций)
docker exec tick_php php bin/phpunit

# E2E-сценарий (регистрация → площадка → событие → бронь → оплата через мок-банк → возврат)
powershell -ExecutionPolicy Bypass -File scripts\e2e.ps1        # ожидаем ALL_E2E_OK

# Полный платёжный сценарий (checkout-страница, decline, рестарт платежа, charge)
powershell -ExecutionPolicy Bypass -File scripts\bank_flow.ps1  # ожидаем BANK_FLOW_OK
```

## Основные API

Публичные: `GET /api/events`, `GET /api/events/{id}`, `GET /api/events/{id}/seats`, `GET /api/events/search?query=`, `POST /api/auth/register`, `POST /api/auth/login`.

Пользователь (JWT): `GET/POST /api/orders/*` (бронь, мои заказы, оплата, отмена), `GET /api/venues/*`.

Админ (ROLE_ADMIN): `GET /api/admin/events`, `POST /api/admin/orders/{id}/refund`, `GET /api/admin/analytics`, `GET /api/admin/tickets/{code}`, плюс общие endpoints создания площадок/событий.

Документация OpenAPI: `GET /api/doc`.
