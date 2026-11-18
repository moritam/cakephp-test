# syntax=docker/dockerfile:1
#
# Todo App 本番用イメージ (PHP 8.4 + Apache + SQLite)
# 使い方は docs/DEPLOY.md を参照。amd64 / arm64 (Oracle Cloud Ampere) の両方でビルドできる。

# --- 1. フロントエンド (Vue) のビルド ----------------------------------------
FROM node:22-alpine AS frontend
WORKDIR /build/frontend
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ ./
# vite.config.js の outDir により /build/webroot/app に出力される
RUN npm run build

# --- 2. PHP の依存ライブラリ (開発用は入れない) ---------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist \
    --ignore-platform-req=ext-intl
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# --- 3. 実行用イメージ ----------------------------------------------------------
FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev \
    && docker-php-ext-install -j"$(nproc)" intl opcache \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/security.conf /etc/apache2/conf-available/zz-security.conf
RUN a2enconf zz-security

WORKDIR /var/www/html
COPY --from=vendor /app ./
COPY --from=frontend /build/webroot/app ./webroot/app

# app_local.php は秘密情報を持たないひな形をそのまま使う (値はすべて環境変数で渡す)
RUN cp config/app_local.example.php config/app_local.php \
    && rm -rf tmp logs && mkdir -p tmp/cache/models tmp/cache/persistent tmp/cache/views tmp/cache/rate_limit logs \
    && chown -R www-data:www-data tmp logs \
    && mkdir -p /data && chown www-data:www-data /data

COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

ENV DEBUG=false \
    DATABASE_PATH=/data/todo_app.sqlite \
    BACKUP_DIR=/data/backups

VOLUME ["/data"]
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD php -r '$s = @fsockopen("127.0.0.1", 80); exit($s ? 0 : 1);'

ENTRYPOINT ["app-entrypoint"]
CMD ["apache2-foreground"]
