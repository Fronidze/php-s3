FROM php:8.1.9-fpm-alpine
WORKDIR /app

RUN apk update \
    && apk add bash vim \
    postgresql-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install pgsql pdo_pgsql

RUN pecl install redis \
    && docker-php-ext-enable redis \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

COPY /app /app