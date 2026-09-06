# Build stage
FROM php:8.5-fpm AS builder

RUN apt-get update && apt-get install -y \
    ca-certificates \
    curl \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    librabbitmq-dev \
    && pecl install redis amqp \
    && docker-php-ext-enable redis amqp \
    && docker-php-ext-install pdo pdo_pgsql intl zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY app/composer.json app/composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader \
    && composer dump-autoload --no-dev --classmap-authoritative

# Production stage
FROM php:8.5-fpm

RUN apt-get update && apt-get install -y \
    ca-certificates \
    curl \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    librabbitmq-dev \
    && pecl install redis amqp \
    && docker-php-ext-enable redis amqp \
    && docker-php-ext-install pdo pdo_pgsql intl zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY --from=builder /var/www/vendor ./vendor
COPY app/ ./

# Generate JWT keys if not present
RUN if [ ! -f config/jwt/private.pem ]; then php bin/console lexik:jwt:generate-keypair -q; fi