#!/bin/sh
set -e

echo "Configurando Laravel..."

chown -R www-data:www-data storage bootstrap/cache || true

if [ "${SKIP_MIGRATIONS:-false}" != "true" ]; then
    echo "Limpiando caché..."
    php artisan optimize:clear

    echo "Ejecutando migraciones..."
    php artisan migrate --force

    echo "Ejecutando seeders..."
    php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ -L public/storage ] || [ -e public/storage ]; then
    rm -rf public/storage
fi

php artisan storage:link || true

PORT="${PORT:-10000}"

sed -i "s/listen 80;/listen ${PORT};/" /etc/nginx/conf.d/default.conf

echo "Iniciando Nginx y PHP-FPM en el puerto ${PORT}..."

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
