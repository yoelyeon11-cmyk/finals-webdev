# Cloudrobe — production image for Railway
FROM php:8.2-cli-alpine AS php-base

RUN apk add --no-cache \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    openssl \
    && docker-php-ext-install -j$(nproc) \
        intl \
        opcache \
        pdo_mysql \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# --- Composer (needed before npm — package.json references vendor/symfony/ux-turbo) ---
FROM php-base AS vendor

COPY composer.json composer.lock symfony.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# --- Webpack Encore assets ---
FROM node:20-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json postcss.config.mjs webpack.config.js ./
COPY assets ./assets
COPY config/packages/webpack_encore.yaml ./config/packages/webpack_encore.yaml
COPY --from=vendor /app/vendor ./vendor
RUN npm ci && npm run build

# --- Application image ---
FROM php-base AS runtime

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --classmap-authoritative --no-dev \
    && php bin/console assets:install public --env=prod --no-interaction \
    && mkdir -p var/cache var/log config/jwt public/uploads/products public/uploads/profiles \
    && chmod -R 775 var config/jwt public/uploads

ENV APP_ENV=prod \
    APP_DEBUG=0

EXPOSE 8080

COPY scripts/railway-start.sh /usr/local/bin/railway-start.sh
RUN sed -i 's/\r$//' /usr/local/bin/railway-start.sh \
    && chmod +x /usr/local/bin/railway-start.sh

CMD ["railway-start.sh"]
