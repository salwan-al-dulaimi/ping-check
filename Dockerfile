# Use PHP with FPM
FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (for caching)
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy the rest of the application
COPY . .

# Ensure required Laravel directories exist and have correct permissions
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Expose PHP-FPM port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]