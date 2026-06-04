FROM php:8.4-cli

RUN apt-get update && apt-get install -y unzip git libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install --no-progress --no-interaction

COPY . .

CMD ["vendor/bin/phpunit"]
