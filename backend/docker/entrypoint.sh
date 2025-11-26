#!/bin/bash
set -e

# Run composer install if needed
if [ ! -d "/var/www/html/vendor" ]; then
    composer install --no-interaction --no-progress
fi

# Regenerate autoloader to fix Windows/Docker sync issues
echo "Regenerating autoloader..."
composer dump-autoload --no-interaction

# Clear Symfony cache
echo "Clearing Symfony cache..."
php bin/console cache:clear --no-interaction || true

# Run database migrations (skip if error)
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "Migrations failed, continuing..."

# Execute the main command
exec "$@"
