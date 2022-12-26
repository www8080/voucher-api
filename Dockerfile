FROM php:8.1-fpm

# Copy composer.lock and composer.json
COPY composer.lock composer.json /var/www/html/voucher

# Set working directory
WORKDIR /var/www/html/voucher

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    nano \
    unzip \
    git \
    curl \
    libzip-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl mysqli docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN docker-php-ext-configure gd --with-gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/
RUN docker-php-ext-install gd

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copy existing application directory contents
COPY . /var/www/html/voucher

# Copy existing application directory permissions
COPY --chown=www:www . /var/www/htmlvoucher

# Change current user to www
USER www

RUN composer install
RUN chmod +x artisan
#RUN composer dump-autoload --optimize && composer run-script post-install-cmd
#CMD php artisan serve --host 0.0.0.0 --port 8000
RUN php artisan migrate --path=database/migrations/2022_12_23_021611_create_vouchers_table.php

# Expose port 8000 and start php-fpm server
EXPOSE 8000
CMD ["php-fpm"]
