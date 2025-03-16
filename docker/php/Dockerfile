FROM php:8.1-fpm

# Instalar extensiones o paquetes necesarios
RUN apt-get update && apt-get install -y \
    curl vim git zip unzip git libpq-dev libzip-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg

# Instalar Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
#COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar el directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de Laravel (asegúrate de que los permisos estén correctos)
COPY . .

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
