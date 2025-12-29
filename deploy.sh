#!/bin/bash

# ============================================
# Laravel Deployment Script with Detailed Logs
# ============================================

set -e

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored messages
print_message() {
    local color=$1
    local message=$2
    echo -e "${color}===> ${message}${NC}"
}

print_error() {
    print_message "$RED" "ERROR: $1"
}

print_success() {
    print_message "$GREEN" "SUCCESS: $1"
}

print_info() {
    print_message "$BLUE" "INFO: $1"
}

print_warning() {
    print_message "$YELLOW" "WARNING: $1"
}

# Start deployment
echo ""
print_info "=========================================="
print_info "Starting Deployment Process"
print_info "Time: $(date '+%Y-%m-%d %H:%M:%S')"
print_info "=========================================="
echo ""

# Change to script directory
cd "$(dirname "$0")"
print_success "Changed to project directory: $(pwd)"

# Check for composer
if [ -x "$HOME/bin/composer" ]; then
    COMPOSER="$HOME/bin/composer"
    print_info "Using composer from: $HOME/bin/composer"
elif command -v composer &> /dev/null; then
    COMPOSER="composer"
    print_info "Using system composer"
else
    print_error "Composer not found!"
    exit 1
fi

# Check composer version
COMPOSER_VERSION=$($COMPOSER --version 2>/dev/null || echo "unknown")
print_info "Composer version: $COMPOSER_VERSION"

# Set branch
BRANCH="${DEPLOY_GIT_BRANCH:-main}"
print_info "Target branch: $BRANCH"

# Check current git status
print_info "Current git status:"
git status --short

# Get current commit
CURRENT_COMMIT=$(git rev-parse HEAD)
print_info "Current commit: $CURRENT_COMMIT"

# Fetch latest changes
echo ""
print_info "Fetching latest changes from origin/$BRANCH..."
if git fetch origin "$BRANCH"; then
    print_success "Git fetch completed successfully"
else
    print_error "Git fetch failed!"
    exit 1
fi

# Get remote commit
REMOTE_COMMIT=$(git rev-parse "origin/$BRANCH")
print_info "Remote commit: $REMOTE_COMMIT"

# Check if update is needed
if [ "$CURRENT_COMMIT" = "$REMOTE_COMMIT" ]; then
    print_warning "No new changes detected. Already up to date!"
else
    print_info "New changes detected. Updating..."
    
    # Show commit differences
    echo ""
    print_info "Commits to be applied:"
    git log --oneline --decorate "$CURRENT_COMMIT..$REMOTE_COMMIT"
    
    # Show file changes
    echo ""
    print_info "Files that will be changed:"
    git diff --name-status "$CURRENT_COMMIT" "$REMOTE_COMMIT"
fi

# Reset to remote branch
echo ""
print_info "Resetting to origin/$BRANCH..."

# Backup deploy.sh if it was modified locally
if git diff --name-only | grep -q "deploy.sh"; then
    print_warning "deploy.sh has local changes, backing up..."
    cp deploy.sh deploy.sh.backup
    RESTORE_DEPLOY=true
else
    RESTORE_DEPLOY=false
fi

if git reset --hard "origin/$BRANCH"; then
    NEW_COMMIT=$(git rev-parse HEAD)
    print_success "Reset completed. New commit: $NEW_COMMIT"
    
    # Restore deploy.sh if it was backed up
    if [ "$RESTORE_DEPLOY" = true ] && [ -f deploy.sh.backup ]; then
        print_info "Restoring local deploy.sh changes..."
        mv deploy.sh.backup deploy.sh
        chmod +x deploy.sh
        print_success "deploy.sh restored"
    fi
else
    print_error "Git reset failed!"
    exit 1
fi

# Check if proc_open is available
echo ""
print_info "Checking PHP configuration..."
if php -r "if (!function_exists('proc_open')) { echo 'disabled'; exit(1); }" 2>/dev/null; then
    print_success "proc_open is available"
else
    print_error "proc_open is disabled in PHP configuration!"
    print_warning "This may cause issues with Laravel commands"
    print_info "Continuing anyway..."
fi

# Install/Update dependencies
echo ""
print_info "Installing/Updating composer dependencies..."
if $COMPOSER install --no-dev --optimize-autoloader --no-interaction --no-scripts 2>&1 | tee /tmp/composer-install.log; then
    print_success "Composer install completed"
    
    # Show installed packages count
    PACKAGE_COUNT=$($COMPOSER show --no-dev 2>/dev/null | wc -l)
    print_info "Total packages installed: $PACKAGE_COUNT"
    
    # Manually run dump-autoload with --no-scripts to avoid package:discover
    print_info "Generating optimized autoload files..."
    if $COMPOSER dump-autoload --optimize --no-dev --no-scripts 2>&1; then
        print_success "Autoload files generated"
    else
        print_warning "Autoload generation had warnings, continuing..."
    fi
    
    # Manually generate package manifest (replaces package:discover)
    print_info "Generating package manifest manually..."
    php -d disable_functions= <<'PHP' 2>&1 || print_warning "Manual package discovery skipped (non-critical)"
<?php
require __DIR__ . '/vendor/autoload.php';
try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $manifest = new \Illuminate\Foundation\PackageManifest(
        new \Illuminate\Filesystem\Filesystem, 
        $app->basePath(), 
        $app->bootstrapPath('cache')
    );
    $manifest->build();
    echo "Package manifest generated successfully\n";
} catch (Exception $e) {
    echo "Warning: " . $e->getMessage() . "\n";
}
PHP
    print_success "Package manifest generation attempted"
else
    print_error "Composer install failed!"
    print_info "Check log at: /tmp/composer-install.log"
    exit 1
fi

# Clear caches before migration
echo ""
print_info "Clearing existing caches..."
php artisan cache:clear 2>/dev/null || print_warning "Cache clear skipped"
php artisan config:clear 2>/dev/null || print_warning "Config clear skipped"
php artisan route:clear 2>/dev/null || print_warning "Route clear skipped"
php artisan view:clear 2>/dev/null || print_warning "View clear skipped"
print_success "Caches cleared"

# Run migrations
echo ""
print_info "Running database migrations..."
if php artisan migrate --force 2>&1 | tee /tmp/migrate.log; then
    print_success "Migrations completed"
else
    print_warning "Migrations had warnings (check /tmp/migrate.log)"
fi

# Cache configuration
echo ""
print_info "Caching configuration..."
if php artisan config:cache 2>&1; then
    print_success "Configuration cached"
else
    print_error "Configuration cache failed!"
fi

# Cache routes
echo ""
print_info "Caching routes..."
if php artisan route:cache 2>&1; then
    print_success "Routes cached"
else
    print_error "Route cache failed!"
fi

# Cache views
echo ""
print_info "Caching views..."
if php artisan view:cache 2>&1; then
    print_success "Views cached"
else
    print_error "View cache failed!"
fi

# Optimize application
echo ""
print_info "Optimizing application..."
if php artisan optimize 2>&1; then
    print_success "Application optimized"
else
    print_warning "Optimization had warnings"
fi

# Set permissions
echo ""
print_info "Setting permissions..."
if chmod -R ug+rwx storage bootstrap/cache 2>&1; then
    print_success "Permissions set successfully"
else
    print_error "Failed to set permissions"
fi

# Restart queue workers if supervisor is available
echo ""
if command -v supervisorctl &> /dev/null; then
    print_info "Restarting queue workers..."
    sudo supervisorctl restart all 2>/dev/null || print_warning "Supervisor restart skipped"
else
    print_warning "Supervisor not found, queue workers not restarted"
fi

# Clear OPcache if available
echo ""
print_info "Clearing OPcache..."
if command -v cachetool &> /dev/null; then
    cachetool opcache:reset 2>/dev/null || print_warning "OPcache reset skipped"
else
    print_warning "cachetool not found, OPcache not cleared"
fi

# Final status
echo ""
print_info "=========================================="
print_success "Deployment Completed Successfully!"
print_info "=========================================="
echo ""
print_info "Deployment Summary:"
print_info "  - Previous commit: $CURRENT_COMMIT"
print_info "  - Current commit: $NEW_COMMIT"
print_info "  - Branch: $BRANCH"
print_info "  - Time: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Show application info
print_info "Application Information:"
php artisan --version 2>/dev/null || echo "Laravel version: unknown"
php artisan env 2>/dev/null || echo "Environment: unknown"

echo ""
print_success "Deployment script finished!"
echo ""