#!/bin/bash
set -e

# Run composer install if needed
if [ ! -d "/var/www/html/vendor" ]; then
    composer install --no-interaction --no-progress
fi

# Run database migrations
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Execute the main command
exec "$@"
