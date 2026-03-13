FROM php:8.2-cli-alpine

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    icu-dev \
    sqlite-dev \
    mysql-client \
    && docker-php-ext-install \
    pdo_mysql \
    pdo_sqlite \
    mbstring \
    bcmath \
    intl \
    zip \
    dom \
    xml \
    xmlwriter

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY src/ ./
RUN composer install --no-interaction --no-progress --optimize-autoloader

COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000

CMD ["entrypoint.sh"]
