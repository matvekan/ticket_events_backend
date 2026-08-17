CREATE TABLE IF NOT EXISTS default.seat_reservations (
    order_id String,
    user_id String,
    amount Int64,
    timestamp DateTime
) ENGINE = MergeTree()
ORDER BY timestamp;

CREATE TABLE IF NOT EXISTS default.order_payments (
    order_id String,
    user_id String,
    amount Int64,
    timestamp DateTime
) ENGINE = MergeTree()
ORDER BY timestamp;

CREATE TABLE IF NOT EXISTS default.order_cancellations (
    order_id String,
    user_id String,
    amount Int64,
    timestamp DateTime
) ENGINE = MergeTree()
ORDER BY timestamp;

CREATE TABLE IF NOT EXISTS default.order_refunds (
    order_id String,
    user_id String,
    amount Int64,
    timestamp DateTime
) ENGINE = MergeTree()
ORDER BY timestamp;
