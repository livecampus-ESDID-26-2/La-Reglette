#!/bin/sh
set -e

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

php bin/console cache:clear --no-interaction

exec php -S 0.0.0.0:8000 -t public public/index.php
