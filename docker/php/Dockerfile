FROM php:8.2-fpm

# Instalar extensiones o paquetes necesarios
RUN apt-get update && apt-get install -y \
    libjpeg-dev libpng-dev libwebp-dev libfreetype6-dev \
    zip unzip git curl vim libzip-dev libpq-dev \
    && docker-php-ext-configure gd \
        --with-jpeg \
        --with-webp \
        --with-freetype \
    && docker-php-ext-install pdo pdo_mysql zip gd \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
#COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar el directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de Laravel (asegúrate de que los permisos estén correctos)
COPY ./src .

# Instalar dependencias de PHP, incluyendo JWT-Auth
RUN composer install --no-dev --optimize-autoloader
RUN composer require tymon/jwt-auth
RUN php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
RUN php artisan jwt:secret

# Permisos adecuados para storage y bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache

# Exponer el puerto 9000 para PHP-FPMEXPOSE 9000
EXPOSE 9000
