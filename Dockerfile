# ============================================
# Stage 1: Install PHP dependencies
# ============================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs


# ============================================
# Stage 2: Build frontend assets
# ============================================
FROM node:22-alpine AS assets

WORKDIR /app

# Build args for Vite (baked into JS at build time)
ARG VITE_REVERB_APP_KEY=jiglychaz3ikfsxiamak
ARG VITE_REVERB_HOST=localhost
ARG VITE_REVERB_PORT=8080
ARG VITE_REVERB_SCHEME=http

ENV VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY}
ENV VITE_REVERB_HOST=${VITE_REVERB_HOST}
ENV VITE_REVERB_PORT=${VITE_REVERB_PORT}
ENV VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}

COPY package.json package-lock.json ./
RUN npm ci --include=optional

COPY vite.config.js ./
COPY resources/ ./resources/
# Tailwind scans blade files for classes
COPY app/ ./app/
# Flux UI CSS lives in vendor — needed for the @import in app.css
COPY --from=vendor /app/vendor ./vendor

RUN npm run build


# ============================================
# Stage 3: Production image (Nginx + PHP-FPM)
# ============================================
FROM php:8.4-fpm-alpine

# Install system dependencies + nginx + supervisor
RUN apk add --no-cache \
    nginx \
    sqlite-dev \
    icu-dev \
    oniguruma-dev \
    supervisor \
    curl \
    && docker-php-ext-install \
        pdo_sqlite \
        intl \
        mbstring \
        pcntl \
    && rm -rf /var/cache/apk/*

# PHP-FPM config tweaks for production
RUN sed -i 's/^pm.max_children = .*/pm.max_children = 20/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^pm.start_servers = .*/pm.start_servers = 4/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^pm.min_spare_servers = .*/pm.min_spare_servers = 2/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^pm.max_spare_servers = .*/pm.max_spare_servers = 6/' /usr/local/etc/php-fpm.d/www.conf

WORKDIR /app

# Copy application code
COPY . .

# Copy built assets from stage 1
COPY --from=assets /app/public/build ./public/build

# Copy vendor from stage 2
COPY --from=vendor /app/vendor ./vendor

# Create SQLite database & storage directories
RUN mkdir -p database storage/app/public storage/framework/{cache,sessions,testing,views} storage/logs \
    && touch database/database.sqlite \
    && chown -R www-data:www-data /app \
    && chmod -R 775 storage bootstrap/cache database

# Copy configs
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expose ports: Nginx (80) + Reverb WebSocket (8080)
EXPOSE 80 8080

ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
