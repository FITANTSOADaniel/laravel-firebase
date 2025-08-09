#!/usr/bin/env bash
set -e

# 1) Si la variable FIREBASE_CREDENTIALS_JSON est présente, la sauver en fichier
if [ -n "$FIREBASE_CREDENTIALS_JSON" ]; then
  echo "Writing firebase credentials..."
  mkdir -p /var/www/storage/firebase
  echo "$FIREBASE_CREDENTIALS_JSON" > /var/www/firebase_credentials.json
  chmod 600 /var/www/firebase_credentials.json
fi

# 2) S'assurer que la clé APP_KEY existe (générer temporairement si absente)
if [ -z "$APP_KEY" ]; then
  echo "No APP_KEY found — generating a temporary one..."
  php artisan key:generate --force
fi

# 3) Optionnel : exécuter les migrations si nécessaire (désactiver si tu n'en veux pas)
if [ "$RUN_MIGRATIONS" = "true" ]; then
  echo "Running migrations..."
  php artisan migrate --force || true
fi

# 4) Mettre en cache (safe, ignore erreur si absent)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# 5) Lancer le serveur sur le PORT fourni par Railway (ou 8000 par défaut)
PORT=${PORT:-8000}
echo "Starting Laravel on 0.0.0.0:$PORT"
php artisan serve --host=0.0.0.0 --port=$PORT
