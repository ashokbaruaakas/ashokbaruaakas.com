# syntax=docker/dockerfile:1

# ---------- Stage 1: PHP base (extensions compiled once, shared by builder and runtime) ----------
FROM php:8.4-fpm-alpine AS php_base

RUN apk add --no-cache \
    bash curl fcgi \
    libpng libjpeg-turbo freetype \
    oniguruma libxml2

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    oniguruma-dev libxml2-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql mbstring exif pcntl bcmath gd opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear


# ---------- Stage 2: PHP builder ----------
FROM php_base AS php_builder

WORKDIR /app

RUN apk add --no-cache git zip unzip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .

RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

RUN composer dump-autoload --optimize --no-dev


# ---------- Stage 3: Node builder ----------
# Needs PHP because the Wayfinder Vite plugin runs artisan during the build.
FROM php_base AS node_builder

WORKDIR /app

RUN apk add --no-cache nodejs npm

# Install dependencies before copying the source so this layer stays cached
# until the lockfile changes.
COPY package.json package-lock.json .npmrc ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --no-audit --no-fund

COPY --from=php_builder /app ./

RUN npm run build:ssr


# ---------- Stage 4: PHP-FPM Runtime (app, queue, scheduler) ----------
FROM php_base AS app_runtime

WORKDIR /var/www

COPY --from=php_builder /app ./
COPY --from=node_builder /app/public/build ./public/build
COPY --from=node_builder /app/bootstrap/ssr ./bootstrap/ssr

RUN find resources -mindepth 1 -maxdepth 1 ! -name 'views' -exec rm -rf {} +

RUN chown -R www-data:www-data storage bootstrap/cache \
    && ln -sf /var/www/storage/app/public /var/www/public/storage

COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf
RUN chmod +x /var/www/scripts/post-deployment.sh

USER www-data

CMD ["php-fpm"]


# ---------- Stage 5: Nginx runtime ----------
FROM nginx:alpine AS web_runtime

WORKDIR /var/www

COPY --from=app_runtime /var/www/public ./public

COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template

CMD ["nginx", "-g", "daemon off;"]


# ---------- Stage 6: Node SSR runtime ----------
FROM node:24-alpine AS ssr_runtime

WORKDIR /app

ENV NODE_ENV=production

# The SSR bundle only imports runtime dependencies (vue, @inertiajs/vue3), so
# skip devDependencies to keep the image small.
COPY package.json package-lock.json .npmrc ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --omit=dev --no-audit --no-fund

COPY --from=node_builder /app/bootstrap/ssr ./bootstrap/ssr

USER node

CMD ["node", "bootstrap/ssr/app.js"]
