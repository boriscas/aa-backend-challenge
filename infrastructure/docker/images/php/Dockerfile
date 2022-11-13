FROM php:8.1-apache-buster

LABEL maintainer="boris.castagna+aa@gmail.com"

ENV DEBIAN_FRONTEND noninteractive

RUN set -eux \
    && apt-get update && apt-get install -y lsb-release \
    && echo "deb http://deb.debian.org/debian $(lsb_release -sc)-backports main" > /etc/apt/sources.list.d/backports.list \
    && apt-get update

RUN set -eux \
        && apt-get install -y --no-install-recommends \
        apt-utils autoconf automake libtool nasm make pkg-config libz-dev build-essential openssl g++ \
        libicu-dev libbz2-dev libc-client-dev \
        libkrb5-dev libxslt1.1 libxslt1-dev locales locales-all \
        python3-pip unzip cron git wget librabbitmq-dev nano curl supervisor \
        libzip-dev libasound2 libatk-bridge2.0-0 libatspi2.0-0 libgtk-3-0 \
        libnspr4 libnss3 libxkbcommon0

RUN set -eux \
    && docker-php-ext-install intl bcmath bz2 soap xsl mysqli pdo pdo_mysql fileinfo exif zip opcache sockets \
    \
    && pecl install apcu \
        && printf "\n" | pecl install amqp \
        && pecl install xdebug \
    \
    && docker-php-ext-enable apcu amqp xdebug

RUN set -eux \
        && apt-get update && apt-get install -y libappindicator1 fonts-liberation libgbm1 xdg-utils \
        && wget https://dl.google.com/dl/linux/direct/google-chrome-stable_current_amd64.deb -O /tmp/chrome.deb \
        && dpkg -i /tmp/chrome.deb; apt-get -fy install \
        && rm /tmp/chrome.deb

RUN set -eux \
        && apt-get autoremove -y \
        && apt-get remove -y autoconf apt-utils automake libtool nasm pkg-config libz-dev build-essential g++ \
        && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* ~/.composer

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_MEMORY_LIMIT -1

COPY --from=composer:2.3.10 /usr/bin/composer /usr/bin/composer

ENV PATH="${PATH}:/root/.composer/vendor/bin"

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN a2enmod rewrite

RUN usermod -u 1000 www-data \
        && usermod -G staff www-data \
        && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
        && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

CMD ["apache2-foreground"]
