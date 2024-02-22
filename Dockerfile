FROM php:8.2-cli

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y git \
    && docker-php-ext-configure pcntl --enable-pcntl \
    && docker-php-ext-install pcntl \
    && pecl install ast-1.1.1 \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug ast \
    && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY . /usr/src/myapp
WORKDIR /usr/src/myapp

RUN php composer.phar install

CMD [ "php", "-a"]
