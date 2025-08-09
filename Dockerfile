FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mbstring exif pcntl bcmath zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 755 /var/www/storage /var/www/bootstrap/cache

EXPOSE 10000

CMD bash -c "\
  if [ -n \"\$FIREBASE_CREDENTIALS_JSON\" ]; then \
    echo \"Création du fichier firebase_credentials.json...\"; \
    echo \"\$FIREBASE_CREDENTIALS_JSON\" > /var/www/firebase_credentials.json; \
    chmod 600 /var/www/firebase_credentials.json; \
  else \
    echo '⚠️ Variable FIREBASE_CREDENTIALS_JSON non définie.'; \
  fi; \
  if [ -z \"\$APP_KEY\" ]; then \
    echo '⚠️ APP_KEY non définie — génération temporaire...'; \
    php artisan key:generate --force; \
  fi; \
  php artisan config:cache || true; \
  php artisan route:cache || true; \
  php artisan view:cache || true; \
  PORT=\${PORT:-10000}; \
  echo \"Lancement de Laravel sur le port \$PORT...\"; \
  php artisan serve --host=0.0.0.0 --port=\$PORT \
"
