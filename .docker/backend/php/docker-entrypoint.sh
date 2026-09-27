#!/bin/sh
set -e

echo "🚀 RIMeF backend - entrypoint"

cd /var/www/html

# Aligne les droits de vendor avec l'utilisateur courant si besoin
if [ -d "vendor" ]; then
  if [ "$(stat -c '%u' vendor 2>/dev/null)" != "$(id -u)" ] || [ "$(stat -c '%g' vendor 2>/dev/null)" != "$(id -g)" ]; then
    if ! chown -R "$(id -u):$(id -g)" vendor 2>/dev/null; then
      echo "⚠️  Impossible de corriger les droits de vendor, suppression..."
      rm -rf vendor 2>/dev/null || echo "⚠️  Suppression impossible, réinstallation lancée quand même"
    fi
  fi
fi

# Installation des dépendances si vendor absent
if [ ! -f "vendor/autoload.php" ]; then
  echo "📦 Installation des dépendances Composer..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
  echo "✅ Dépendances installées"
else
  # Réinstalle si composer.json a été modifié après composer.lock
  if [ -f "composer.json" ] && [ "composer.json" -nt "vendor/autoload.php" ]; then
    echo "📦 composer.json modifié, réinstallation..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
  fi
fi

# Storage / bootstrap/cache : créer + rendre inscriptibles
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

# Génère APP_KEY si vide (premier démarrage)
if [ -f ".env" ] && ! grep -q "^APP_KEY=base64:" .env; then
  echo "🔑 Génération de APP_KEY..."
  php artisan key:generate --force
fi

echo "🎯 Démarrage : $@"
exec "$@"
