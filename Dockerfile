# Stage 1
FROM php:7.4-cli AS build
WORKDIR /app
RUN apt-get update && apt-get install -y zip unzip libzip-dev
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY composer.json .
COPY composer.lock .
RUN composer install

# Final stage
FROM php:7.4-apache
WORKDIR /var/www/html/

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN apt-get update && apt-get install -y zip unzip libzip-dev \
    && docker-php-ext-configure zip --with-zip \
    && docker-php-ext-install -j$(nproc) zip

COPY --from=build /app/vendor .
COPY web .
COPY commands .
