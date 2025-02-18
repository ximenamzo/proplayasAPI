FROM php:8.1-fpm

# Instalar extensiones o paquetes necesarios
RUN apt-get update && apt-get install -y \
    curl vim zip unzip git libpq-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurar el directorio de trabajo
WORKDIR /var/www/html

