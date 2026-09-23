#!/bin/sh
set -e

# Aseguramos permisos por si el volumen montado los ha pisado
chown -R www-data:www-data storage bootstrap/cache || true

# Migramos y sembramos datos (seguro repetir gracias a --force y a que
# migrate no vuelve a aplicar migraciones ya ejecutadas)
php artisan migrate --force
php artisan db:seed --force

if [ -L public/storage ] || [ -e public/storage ]; then
    rm -f public/storage
fi
php artisan storage:link

exec "$@"