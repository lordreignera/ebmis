#!/bin/bash

# Quick Deploy Script for Digital Ocean
# Run this script on your server after git pull

echo "╔════════════════════════════════════════════════════════════╗"
echo "║         DIGITAL OCEAN - QUICK DEPLOYMENT                   ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR"

echo "📁 Project Directory: $PROJECT_DIR"
echo ""

# Step 1: Git Pull
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📥 Step 1: Pulling latest code from repository..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
git pull origin master

if [ $? -ne 0 ]; then
    echo "❌ Git pull failed! Please check your repository connection."
    exit 1
fi

echo "✅ Code updated successfully"
echo ""

# Step 2: Install Dependencies
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📦 Step 2: Installing/Updating dependencies..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
composer install --no-dev --optimize-autoloader --no-interaction

if [ $? -ne 0 ]; then
    echo "⚠️  Composer install had issues, continuing anyway..."
fi

echo "✅ Dependencies updated"
echo ""

# Step 3: Fix Route Caching
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔧 Step 3: Fixing route caching issues..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
php fix_routes_production.php

if [ $? -ne 0 ]; then
    echo "❌ Route fix failed! Trying manual cache clear..."
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "✅ Routes fixed"
echo ""

# Step 4: Set Permissions
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔒 Step 4: Setting file permissions..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo "✅ Permissions set"
echo ""

# Step 5: Restart Services
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔄 Step 5: Restarting web services..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Reload Nginx
if systemctl is-active --quiet nginx; then
    sudo systemctl reload nginx
    echo "✅ Nginx reloaded"
fi

# Restart PHP-FPM (try different versions)
if systemctl is-active --quiet php8.2-fpm; then
    sudo systemctl restart php8.2-fpm
    echo "✅ PHP 8.2-FPM restarted"
elif systemctl is-active --quiet php8.1-fpm; then
    sudo systemctl restart php8.1-fpm
    echo "✅ PHP 8.1-FPM restarted"
elif systemctl is-active --quiet php8.0-fpm; then
    sudo systemctl restart php8.0-fpm
    echo "✅ PHP 8.0-FPM restarted"
elif systemctl is-active --quiet php7.4-fpm; then
    sudo systemctl restart php7.4-fpm
    echo "✅ PHP 7.4-FPM restarted"
else
    echo "⚠️  Could not find PHP-FPM service"
fi

# Restart Laravel workers if running
if systemctl is-active --quiet laravel-worker; then
    sudo systemctl restart laravel-worker
    echo "✅ Laravel workers restarted"
fi

echo ""

# Step 6: Verify Login Route
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Step 6: Verifying login route..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
php artisan route:list --name=login

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║              DEPLOYMENT COMPLETE! ✅                        ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "🎉 Your application has been deployed successfully!"
echo ""
echo "📋 Next Steps:"
echo "  1. Test login: https://your-domain.com/login"
echo "  2. (Optional) Run database optimization:"
echo "     php optimize_database_safe.php"
echo "  3. (Optional) Add performance indexes:"
echo "     php add_missing_indexes.php"
echo ""
echo "📊 Check logs if needed:"
echo "  tail -f storage/logs/laravel.log"
echo ""
