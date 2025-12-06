# Dockerfile for Symfony API
FROM php:8.3-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    postgresql-dev \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    intl \
    zip \
    gd \
    opcache \
    mbstring

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Configure PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Development stage
FROM base AS dev

# Install Xdebug for development
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Copy composer files first for better caching
COPY composer.json composer.lock symfony.lock ./

# Install dependencies
RUN composer install --prefer-dist --no-scripts --no-progress --no-interaction --no-autoloader

# Copy application files
COPY . .

# Generate autoloader and run scripts
RUN composer dump-autoload --optimize && \
    mkdir -p var/cache var/log && \
    chown -R www-data:www-data var/

EXPOSE 9000

CMD ["php-fpm"]

# Production stage
FROM base AS prod

# Override OPcache config for production (disable timestamp validation)
COPY docker/php/opcache.prod.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy composer files first for better caching
COPY composer.json composer.lock symfony.lock ./

# Install dependencies (no dev)
RUN composer install --prefer-dist --no-dev --no-scripts --no-progress --no-interaction --no-autoloader

# Copy application files
COPY . .

# Optimize for production
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative && \
    mkdir -p var/cache var/log && \
    chown -R www-data:www-data var/ && \
    chmod -R 775 var/

# Clear cache
RUN php bin/console cache:clear --env=prod --no-debug --no-warmup || true && \
    php bin/console cache:warmup --env=prod || true

EXPOSE 9000

CMD ["php-fpm"]
