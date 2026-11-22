# Vendra — production image (Apache + PHP 8.4, SQLite).
# Built for free hosting of the public demo; data lives in the container and is reseeded on start
# only when VENDRA_PUBLIC_DEMO=true. Tailwind CSS and fonts are prebuilt in public/, so no Node step.

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

FROM php:8.4-apache

# gd (JPEG/PNG/WebP) and exif: uploaded pictures are re-encoded and stripped of metadata.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" gd exif opcache \
    && apt-get purge -y --auto-remove libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
    && apt-get install -y --no-install-recommends libpng16-16 libjpeg62-turbo libwebp7 libfreetype6 \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod headers deflate \
    && sed -ri 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-vendra.ini

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html

ENV PORT=10000 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=warning \
    DB_CONNECTION=sqlite \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=sync \
    MAIL_MAILER=log

RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod +x docker/entrypoint.sh

EXPOSE 10000
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s CMD php -r 'exit(@file_get_contents("http://127.0.0.1:".getenv("PORT")."/up") === false ? 1 : 0);'
ENTRYPOINT ["docker/entrypoint.sh"]
