# syntax=docker/dockerfile:1
FROM php:8.2-cli

# Installer extensions PHP nécessaires pour Laravel
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mbstring exif pcntl bcmath zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www

# Copier tous les fichiers du projet
COPY . .

# Installer dépendances PHP (optimisées)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Donner les droits nécessaires à Laravel
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Copier script d'entrypoint
COPY ./entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Exposer le port
EXPOSE 10000

# Commande par défaut
ENTRYPOINT ["entrypoint.sh"]
