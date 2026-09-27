# syntax=docker/dockerfile:1.6
FROM php:8.5-fpm-bookworm

# Composer 2 (binaire officiel)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# install-php-extensions : gère deps système + particularités PHP 8.5
# (https://github.com/mlocati/docker-php-extension-installer)
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Extensions PHP requises par Laravel + métier RIMeF
RUN install-php-extensions \
        pdo_mysql \
        bcmath \
        mbstring \
        zip \
        gd \
        intl \
        opcache \
        exif \
        pcntl

# Outils dev/runtime
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

COPY .docker/backend/php/php.ini /usr/local/etc/php/conf.d/zz-rimef.ini
COPY .docker/backend/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
