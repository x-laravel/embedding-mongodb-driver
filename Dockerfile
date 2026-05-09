ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-cli-bookworm

# Install system dependencies and PHP MongoDB extension
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libssl-dev \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

WORKDIR /app
