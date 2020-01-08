FROM php:7.4

# Installation de imagick
RUN apt-get update \
    && apt-get install -y --no-install-recommends libmagickwand-dev \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installation de l'extension ZIP
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev zlib1g-dev \
    && docker-php-ext-install zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Launch tests suite
CMD php vendor/bin/phpunit tests/

# Base dir
WORKDIR /usr/src
COPY vendor vendor
COPY src src
COPY tests tests