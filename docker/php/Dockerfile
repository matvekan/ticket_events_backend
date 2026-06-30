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
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www
