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
rm -rf $PUBLIC_PATH/*
cp -r $APP_PATH/public/* $PUBLIC_PATH/
cp $APP_PATH/public/.htaccess $PUBLIC_PATH/ 2>/dev/null || true

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