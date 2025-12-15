# ==========================================
# Stage 1: Install Vendor Deps (Composer)
# ==========================================
FROM composer:2 as vendor

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install only production dependencies
# - --no-dev: Exclude dev dependencies
# - --ignore-platform-reqs: Avoid PHP version check issues during build
# - --no-autoloader: We'll dump a fresh autoloader later
# - --no-scripts: Don't run post-install scripts yet
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
FROM php:8.4-fpm-alpine as final

# Set working directory
WORKDIR /var/www/html

# Environment variables
ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_ENABLE_CLI=1
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
ENV TZ=America/Bogota

# Install system dependencies & PHP extensions using the official installer script
# This handles all dependency resolution automatically for Alpine
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    pdo_pgsql \
    intl \
    zip \
    gd \
    opcache \
    bcmath \
    pcntl \
    && apk add --no-cache \
    nginx \
    supervisor \
    curl \
    unzip \
    tini

# Copy configuration files
# Ensure these files exist in your project or create them
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Setup non-root user
RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data /var/log/supervisor \
    && touch /tmp/nginx.pid \
    && chown -R www-data:www-data /tmp/nginx.pid \
    && chown -R www-data:www-data /var/lib/nginx \
    && chown -R www-data:www-data /var/log/nginx

# Copy Vendor (from Stage 1)
COPY --from=vendor /app/vendor ./vendor
COPY --from=vendor /usr/bin/composer /usr/bin/composer

# Copy Application Source Code
COPY . .

# Final Composer optimization
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# Set permissions for Laravel storage
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Switch to non-root user
USER www-data

# Expose port
EXPOSE 8080

# Use Tini as init process
ENTRYPOINT ["/sbin/tini", "--"]

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/start-container
USER root
RUN chmod +x /usr/local/bin/start-container
USER www-data

CMD ["/usr/local/bin/start-container"]
