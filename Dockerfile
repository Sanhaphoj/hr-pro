# ============================================================================
# HR PRO — production image for free PaaS hosting (Render / Koyeb / Fly / any).
# PHP 8.3 + SQLite. No external database service required.
# ============================================================================
FROM php:8.3-cli

# --- System libraries + PHP extensions Laravel needs ------------------------
# chromium prints documents to PDF ("ดาวน์โหลด PDF"). The Thai font is
# embedded by the app itself, so no font packages are needed.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libsqlite3-dev \
        libonig-dev \
        unzip \
        git \
        chromium \
    && docker-php-ext-install pdo_sqlite mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# --- Composer ---------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Sensible production defaults. These are real environment variables, so they
# take precedence over .env and make the container self-contained (works with a
# plain `docker run` too). Override any of them on your host's dashboard.
ENV APP_NAME="HR PRO V.2" \
    APP_ENV=production \
    APP_DEBUG=false \
    APP_TIMEZONE=Asia/Bangkok \
    DB_CONNECTION=sqlite \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync \
    LOG_CHANNEL=stderr \
    HRPRO_CHROME_PATH=/usr/bin/chromium \
    PORT=8080

# --- Application ------------------------------------------------------------
COPY . .

RUN cp .env.example .env \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x docker/entrypoint.sh

EXPOSE 8080

CMD ["sh", "docker/entrypoint.sh"]
