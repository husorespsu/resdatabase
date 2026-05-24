FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libssl-dev \
    libicu-dev \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        pdo_mysql \
        gd \
        zip \
        intl \
        opcache \
        mbstring

# Enable Apache modules
RUN a2enmod rewrite headers expires deflate

# Copy custom Apache config
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Copy application files
COPY . /var/www/html/

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies (production)
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/storage/logs \
                /var/www/html/storage/cache \
                /var/www/html/public/uploads/avatars \
                /var/www/html/public/uploads/proposals \
    && chmod -R 775 /var/www/html/storage \
                    /var/www/html/public/uploads

# PHP config for production
RUN echo "display_errors = Off" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "error_log = /var/www/html/storage/logs/php_error.log" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "upload_max_filesize = 20M" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "max_execution_time = 120" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/production.ini

EXPOSE 80

CMD ["apache2-foreground"]
