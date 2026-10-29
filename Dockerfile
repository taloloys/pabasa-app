FROM php:8.5-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
       pdo_mysql bcmath exif pcntl gd intl zip \
    && git clone --depth 1 https://github.com/phpredis/phpredis.git /tmp/phpredis \
    && cd /tmp/phpredis && phpize && ./configure && make -j"$(nproc)" && make install \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/phpredis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]