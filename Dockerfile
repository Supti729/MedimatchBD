# Medimatch BD — production image for Railway (Laravel 13 on FrankenPHP, PHP 8.3).
# The AI service is deployed separately to Hugging Face Spaces (see ai-service/Dockerfile).

# ---------- 1. Front-end assets (Tailwind + Vite) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
# Tailwind scans app/ for class names used by the status enums.
COPY app ./app
RUN npm run build

# ---------- 2. PHP application ----------
FROM dunglas/frankenphp:1-php8.3

RUN install-php-extensions pdo_mysql gd intl zip bcmath opcache pcntl

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080

WORKDIR /app
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Dependencies first (better layer caching), without dev packages.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && chmod +x docker/entrypoint.sh \
    && cp docker/php.ini "$PHP_INI_DIR/conf.d/zz-medimatch.ini"

EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s CMD curl -fsS "http://127.0.0.1:${PORT}/up" || exit 1
ENTRYPOINT ["docker/entrypoint.sh"]
