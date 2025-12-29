#!/bin/bash
set -e

cd "$(dirname "$0")"

if [ -x "$HOME/bin/composer" ]; then
  COMPOSER="$HOME/bin/composer"
else
  COMPOSER="composer"
fi

BRANCH="${DEPLOY_GIT_BRANCH:-main}"

git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"

$COMPOSER install --no-dev --optimize-autoloader

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

chmod -R ug+rwx storage bootstrap/cache

