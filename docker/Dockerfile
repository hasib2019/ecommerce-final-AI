FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev libzip-dev libicu-dev unzip git \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" gd pdo_mysql zip intl bcmath opcache exif \
    && a2enmod rewrite headers expires deflate \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Serve from public/ and let the shipped .htaccess files apply.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && printf "opcache.enable=1\nopcache.validate_timestamps=0\nupload_max_filesize=16M\npost_max_size=20M\nexpose_php=Off\n" > /usr/local/etc/php/conf.d/shopbd.ini

WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/shopbd-entrypoint
RUN chmod +x /usr/local/bin/shopbd-entrypoint

EXPOSE 80
CMD ["shopbd-entrypoint"]
