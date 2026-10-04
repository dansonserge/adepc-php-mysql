# Dev/test image that mimics a typical cPanel host: Apache + mod_rewrite,
# PHP 8.1 (the oldest version this project supports), GD with WebP.
FROM php:8.1-apache
RUN apt-get update \
 && apt-get install -y --no-install-recommends libjpeg62-turbo-dev libpng-dev libwebp-dev libicu-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd pdo_mysql \
 && a2enmod rewrite headers expires \
 && rm -rf /var/lib/apt/lists/*
RUN sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
 && { echo 'upload_max_filesize=64M'; echo 'post_max_size=64M'; echo 'memory_limit=256M'; echo 'display_errors=Off'; echo 'log_errors=On'; } > /usr/local/etc/php/conf.d/adepc.ini
