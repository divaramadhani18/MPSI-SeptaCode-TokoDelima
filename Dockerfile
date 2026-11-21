# --- Tahap 1: build aset Vite (CSS/JS) ---
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# --- Tahap 2: dasar PHP 8.4 + FrankenPHP ---
FROM dunglas/frankenphp:1-php8.4-bookworm AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && install-php-extensions intl zip pdo_mysql bcmath opcache \
    && printf "[opcache]\nopcache.enable=1\nopcache.memory_consumption=128\nopcache.interned_strings_buffer=16\nopcache.max_accelerated_files=20000\nopcache.validate_timestamps=0\nopcache.save_comments=1\n" \
        > "$PHP_INI_DIR/conf.d/99-opcache.ini"

WORKDIR /app

# --- Tahap 3: dependensi Composer ---
FROM base AS dependencies

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist

COPY . .
RUN rm -f bootstrap/cache/*.php public/hot \
    && mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        bootstrap/cache \
    && composer dump-autoload --no-dev --classmap-authoritative

# --- Tahap 4: image akhir ---
FROM base AS runtime

COPY --from=dependencies --chown=www-data:www-data /app /app
COPY --from=assets --chown=www-data:www-data /app/public/build /app/public/build
COPY --chown=www-data:www-data Caddyfile /etc/frankenphp/Caddyfile

RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
    && chown -R www-data:www-data /config/caddy /data/caddy

ENV PORT=80 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=mysql \
    SESSION_DRIVER=cookie \
    SESSION_SECURE_COOKIE=true \
    CACHE_STORE=array \
    QUEUE_CONNECTION=sync \
    APP_CONFIG_CACHE=/tmp/config.php \
    APP_EVENTS_CACHE=/tmp/events.php \
    VIEW_COMPILED_PATH=/tmp/storage/framework/views \
    XDG_DATA_HOME=/tmp/caddy-data \
    XDG_CONFIG_HOME=/tmp/caddy-config \
    MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt

USER www-data

CMD ["sh", "-c", "mkdir -p /tmp/storage/framework/views /tmp/storage/framework/cache/data /tmp/storage/framework/sessions /tmp/storage/logs /tmp/caddy-data /tmp/caddy-config && (php artisan config:cache && php artisan view:cache) || true && exec frankenphp run --config /etc/frankenphp/Caddyfile"]