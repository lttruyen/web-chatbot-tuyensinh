# ===== Versions (có thể đổi) =====
ARG PHP_VERSION=8.3
ARG NODE_VERSION=20

# ===== Stage 1: vendor (composer, giữ cả dev) =====
FROM composer:2 AS vendor
WORKDIR /app

# Cài trước để tận dụng cache
COPY composer.json composer.lock* ./
RUN composer install --prefer-dist --no-interaction --no-progress || true

# Copy toàn bộ mã nguồn rồi cài lại để autoload chính xác
COPY . .
RUN composer install --prefer-dist --no-interaction --no-progress \
 && composer clear-cache

# ===== Stage 2: frontend (vite) =====
FROM node:${NODE_VERSION}-alpine AS frontend
WORKDIR /app

# Cài deps (tự phát hiện yarn/pnpm/npm)
COPY package.json package-lock.json* pnpm-lock.yaml* yarn.lock* ./
RUN if [ -f yarn.lock ]; then yarn install --frozen-lockfile; \
    elif [ -f pnpm-lock.yaml ]; then npm i -g pnpm && pnpm i --frozen-lockfile; \
    else npm ci || npm i; fi

# Copy source để build
COPY . .

# Build nếu có vite config; luôn tạo sẵn thư mục build để COPY không lỗi
RUN if [ -f vite.config.ts ] || [ -f vite.config.js ]; then \
      npm run build || npm run build:prod || npm run prod || true; \
    fi && \
    mkdir -p /app/public/build

# ===== Stage 3: runtime (php-fpm) =====
FROM php:${PHP_VERSION}-fpm-alpine AS app
WORKDIR /var/www/html

# Ext PHP cần cho Laravel
RUN set -ex && \
    apk add --no-cache bash git curl unzip icu-dev libzip-dev oniguruma-dev \
      libpng libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
      linux-headers autoconf build-base && \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install pdo pdo_mysql mbstring zip intl gd bcmath && \
    pecl install redis && docker-php-ext-enable redis && \
    rm -rf /tmp/pear

# Opcache (tùy chọn)
RUN { \
  echo 'opcache.enable=1'; \
  echo 'opcache.enable_cli=1'; \
  echo 'opcache.jit=1255'; \
  echo 'opcache.jit_buffer_size=128M'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Copy source + vendor + assets đã build
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# Quyền thư mục cho Laravel
RUN addgroup -g 1000 www && adduser -G www -g www -s /bin/sh -D www && \
    chown -R www:www storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

USER www
EXPOSE 9000
CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]
