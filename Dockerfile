# ---------- Stage 1: PHP builder ----------
FROM php:8.4-fpm-alpine AS php_builder

WORKDIR /app

RUN apk add --no-cache \
    bash curl git zip unzip \
    $PHPIZE_DEPS \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    oniguruma-dev libxml2-dev

RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader

COPY . .

RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

RUN composer dump-autoload --optimize


# ---------- Stage 2: Node builder ----------
FROM php_builder AS node_builder

RUN apk add --no-cache nodejs npm

RUN npm ci && npm run build:ssr


# ---------- Stage 3: PHP-FPM Runtime (app, queue, scheduler) ----------
FROM php:8.4-fpm-alpine AS app_runtime

WORKDIR /var/www

RUN apk add --no-cache \
    bash curl \
    libpng libjpeg-turbo freetype \
    oniguruma libxml2

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    oniguruma-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=php_builder /app ./
COPY --from=node_builder /app/public/build ./public/build
COPY --from=node_builder /app/bootstrap/ssr ./bootstrap/ssr

RUN find resources -mindepth 1 -maxdepth 1 ! -name 'views' -exec rm -rf {} +

RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data storage bootstrap/cache

COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY scripts/post-deployment.sh /var/www/scripts/post-deployment.sh

RUN chmod +x /var/www/scripts/post-deployment.sh

USER www-data

CMD ["php-fpm"]


# ---------- Stage 4: Nginx runtime ----------
FROM nginx:alpine AS web_runtime

WORKDIR /var/www

COPY --from=app_runtime /var/www/public ./public

COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template

CMD ["nginx", "-g", "daemon off;"]


# ---------- Stage 5: Node SSR runtime ----------
FROM node:24-alpine AS ssr_runtime

WORKDIR /app

ENV NODE_ENV=production

COPY --from=node_builder /app/node_modules ./node_modules
COPY --from=app_runtime /var/www/bootstrap/ssr ./bootstrap/ssr

CMD ["node", "bootstrap/ssr/app.js"]
