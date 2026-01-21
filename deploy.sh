#!/bin/bash

echo "🚀 Starting deployment..."

# Configuration
APP_PATH="/home/bhij4149/encitycoffee"
PUBLIC_PATH="/home/bhij4149/public_html/encity.bhinneka.space"
COMPOSER_PATH="$HOME/bin/composer"

cd $APP_PATH

echo "📥 Pulling latest changes..."
git pull origin main

echo "📦 Installing composer dependencies..."
$COMPOSER_PATH install --no-dev --optimize-autoloader --no-scripts 2>&1 || true

echo "🔄 Clearing caches..."
rm -rf storage/framework/cache/* 2>&1 || true
rm -rf storage/framework/views/* 2>&1 || true
rm -rf bootstrap/cache/*.php 2>&1 || true
php artisan config:clear 2>&1 || true
php artisan route:clear 2>&1 || true
php artisan view:clear 2>&1 || true

echo "💾 Caching config..."
php artisan config:cache 2>&1 || true
php artisan route:cache 2>&1 || true
php artisan view:cache 2>&1 || true

echo "📁 Syncing public folder..."

# Backup webhook.php if exists
WEBHOOK_BACKUP="/tmp/webhook_$(date +%s).php"
if [ -f "$PUBLIC_PATH/webhook.php" ]; then
    cp "$PUBLIC_PATH/webhook.php" "$WEBHOOK_BACKUP"
    echo "  ↳ Backed up webhook.php"
fi

# Clear and sync public folder
rm -rf $PUBLIC_PATH/*
cp -r $APP_PATH/public/* $PUBLIC_PATH/
cp $APP_PATH/public/.htaccess $PUBLIC_PATH/ 2>/dev/null || true

echo "🔗 Ensuring storage symlink..."
# Hapus folder storage lama jika bukan symlink, lalu buat symlink ke storage publik Laravel
if [ -d "$PUBLIC_PATH/storage" ] && [ ! -L "$PUBLIC_PATH/storage" ]; then
    rm -rf "$PUBLIC_PATH/storage"
fi

if [ ! -L "$PUBLIC_PATH/storage" ]; then
    ln -s "$APP_PATH/storage/app/public" "$PUBLIC_PATH/storage"
    echo "  ↳ Created storage symlink: $PUBLIC_PATH/storage -> $APP_PATH/storage/app/public"
else
    echo "  ↳ Storage symlink already exists"
fi

# Restore webhook.php
if [ -f "$WEBHOOK_BACKUP" ]; then
    cp "$WEBHOOK_BACKUP" "$PUBLIC_PATH/webhook.php"
    chmod 644 "$PUBLIC_PATH/webhook.php"
    rm "$WEBHOOK_BACKUP"
    echo "  ↳ Restored webhook.php"
fi

# Fix index.php path
cat > $PUBLIC_PATH/index.php << 'INDEXEOF'
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../../encitycoffee/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../../encitycoffee/vendor/autoload.php';

(require_once __DIR__.'/../../encitycoffee/bootstrap/app.php')
    ->handleRequest(Request::capture());
INDEXEOF

chmod 644 $PUBLIC_PATH/index.php

echo "🔐 Setting permissions..."
chmod -R 775 $APP_PATH/storage 2>&1 || true
chmod -R 775 $APP_PATH/bootstrap/cache 2>&1 || true

echo "✅ Deployment completed successfully!"
