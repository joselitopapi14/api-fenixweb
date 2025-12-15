# ==========================================
# Stage 1: Composer dependencies
# ==========================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --optimize-autoloader \
    --classmap-authoritative \
    --no-scripts


# ==========================================
# Stage 2: PHP-FPM (Dokploy)
# ==========================================
FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

ENV TZ=America/Bogota

# ------------------------------------------------
# PHP extensions
# ------------------------------------------------
RUN set -ex \
    && apk add --no-cache \
    icu-libs \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    libpq \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libpq-dev \
    linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    intl \
    zip \
    gd \
    opcache \
    bcmath \
    pcntl \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*


# ------------------------------------------------
# PHP configuration
# ------------------------------------------------
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini


# ------------------------------------------------
# Application
# ------------------------------------------------
COPY --from=vendor /app/vendor ./vendor
COPY . .

# ------------------------------------------------
# Laravel permissions
# ------------------------------------------------
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache


# ------------------------------------------------
# Runtime
# ------------------------------------------------
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
