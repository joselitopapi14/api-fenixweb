# ==========================================
# Stage 1: Install Vendor Deps (Composer)
# ==========================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --no-autoloader \
    --no-scripts

# ==========================================
# Stage 2: Final Production Image
# ==========================================
FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_ENABLE_CLI=1
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
ENV TZ=America/Bogota

# System deps + PHP extensions
RUN set -ex \
    && apk add --no-cache \
    nginx \
    supervisor \
    curl \
    zip \
    unzip \
    icu-libs \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    libpq \
    tini \
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

# PHP / Nginx / Supervisor config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Permissions
RUN mkdir -p /var/log/supervisor \
    && touch /tmp/nginx.pid \
    && chown -R www-data:www-data \
    /var/www/html \
    /var/log/supervisor \
    /var/log/nginx \
    /var/lib/nginx \
    /tmp/nginx.pid

# Vendor from Stage 1
COPY --from=vendor /app/vendor ./vendor
COPY --from=vendor /usr/bin/composer /usr/bin/composer

# App source
COPY . .

# Optimized autoloader
RUN composer dump-autoload \
    --optimize \
    --classmap-authoritative \
    --no-dev

# Laravel writable dirs
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 8080

ENTRYPOINT ["/sbin/tini", "--"]

COPY docker-entrypoint.sh /usr/local/bin/start-container
USER root
RUN chmod +x /usr/local/bin/start-container
USER www-data

CMD ["/usr/local/bin/start-container"]
