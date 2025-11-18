#!/bin/sh
set -e

echo "🚀 OnlyRoll Backend - Starting..."

# Attendre MySQL
echo "⏳ Waiting for MySQL..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
  echo "MySQL is unavailable - sleeping"
  sleep 2
done
echo "✅ MySQL is ready!"

# Environnement PRODUCTION
if [ "$APP_ENV" = "prod" ]; then
    echo "🔧 Production mode"

    # Migrations
    echo "🗄️ Running migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

    # Cache
    echo "🔥 Warming up cache..."
    php bin/console cache:clear --env=prod --no-debug
    php bin/console cache:warmup --env=prod --no-debug
fi

# Environnement DÉVELOPPEMENT
if [ "$APP_ENV" = "dev" ]; then
    echo "🔧 Development mode"

    # Composer
    echo "📦 Installing dependencies..."
    composer install --prefer-dist --no-interaction

    # Base de données
    echo "🗄️ Setting up database..."
    php bin/console doctrine:database:create --if-not-exists --no-interaction
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

    # Fixtures (optionnel)
    echo "🌱 Loading fixtures..."
    php bin/console doctrine:fixtures:load --no-interaction --append || true
fi

# Générer clés JWT
if [ ! -f "config/jwt/private.pem" ]; then
    echo "🔐 Generating JWT keys..."
    php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction
fi

# Permissions
echo "🔒 Fixing permissions..."
chown -R www-data:www-data var/ 2>/dev/null || true
chmod -R 775 var/ 2>/dev/null || true

echo "✅ Backend ready!"

exec "$@"
