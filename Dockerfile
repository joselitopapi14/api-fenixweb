# ==========================================
# Stage 1: Composer dependencies
# ==========================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer update \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --optimize-autoloader \
    --classmap-authoritative \
    --no-scripts


# ==========================================
# Stage 2: PHP-FPM + Nginx (Supervisor)
# ==========================================
FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

ENV TZ=America/Bogota

# ------------------------------------------------
# PHP extensions & System Deps
# ------------------------------------------------
RUN set -ex \
    && apk add --no-cache \
    nginx \
    supervisor \
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

# ------------------------------------------------
# Configuration
# ------------------------------------------------
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ------------------------------------------------
# Application
# ------------------------------------------------
COPY --from=vendor /usr/bin/composer /usr/bin/composer
COPY --from=vendor /app/vendor ./vendor
COPY . .

# ------------------------------------------------
# Regenerate Autoloader & Permissions
# ------------------------------------------------
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --ignore-platform-reqs \
    && mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && mkdir -p /var/log/supervisor /tmp/nginx \
    && touch /var/run/nginx.pid \
    && chown -R www-data:www-data /var/run/nginx.pid /var/lib/nginx /var/log/nginx /var/log/supervisor /tmp/nginx

# ------------------------------------------------
# Runtime
# ------------------------------------------------
USER www-data

# Exponer PUERTO 8080 (Donde escucha Nginx)
EXPOSE 8080

ENTRYPOINT ["/sbin/tini", "--"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
