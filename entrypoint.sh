#!/usr/bin/env bash
set -e

# 1) Écrire le fichier Firebase credentials depuis la variable d'env
if [ -n "$FIREBASE_CREDENTIALS_JSON" ]; then
  echo "📂 Création du fichier firebase_credentials.json..."
  echo "$FIREBASE_CREDENTIALS_JSON" > /var/www/firebase_credentials.json
  chmod 600 /var/www/firebase_credentials.json
else
  echo "⚠️ Variable FIREBASE_CREDENTIALS_JSON non définie."
fi

# 2) Générer APP_KEY si absent
if [ -z "$APP_KEY" ]; then
  echo "⚠️ APP_KEY non définie — génération temporaire..."
  php artisan key:generate --force
fi

# 3) Mise en cache Laravel
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# 4) Lancer Laravel sur le port fourni par Render
PORT=${PORT:-10000}
echo "🚀 Lancement de Laravel sur le port $PORT..."
php artisan serve --host=0.0.0.0 --port=$PORT
