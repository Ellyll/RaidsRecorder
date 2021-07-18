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
WORKDIR /app

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Set new document root
ENV APACHE_DOCUMENT_ROOT /app/web
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN apt-get update && apt-get install -y zip unzip libzip-dev \
    && docker-php-ext-configure zip --with-zip \
    && docker-php-ext-install -j$(nproc) zip

COPY --from=build /app/vendor .
COPY web .
COPY commands .
