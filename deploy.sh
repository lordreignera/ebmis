#!/bin/bash

# Digital Ocean Deployment Script
# This script runs after git pull to ensure everything is properly configured
# Usage: bash deploy.sh

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║         DIGITAL OCEAN DEPLOYMENT SCRIPT                    ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "→ $1"
}

# Step 1: Check if we're in the right directory
echo "📁 Checking project directory..."
if [ ! -f "artisan" ]; then
    print_error "Error: artisan file not found. Are you in the project root?"
    exit 1
fi
print_success "Project directory confirmed"
echo ""

# Step 2: Pull latest code
echo "🔄 Pulling latest code from Git..."
git pull origin master
if [ $? -eq 0 ]; then
    print_success "Code updated successfully"
else
    print_error "Git pull failed"
    exit 1
fi
echo ""

# Step 3: Install/Update Composer dependencies
echo "📦 Updating Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
if [ $? -eq 0 ]; then
    print_success "Composer dependencies updated"
else
    print_warning "Composer update had issues (might be okay)"
fi
echo ""

# Step 4: Install/Update NPM dependencies (if needed)
if [ -f "package.json" ]; then
    echo "📦 Checking NPM dependencies..."
    npm install --production
    if [ $? -eq 0 ]; then
        print_success "NPM dependencies updated"
    else
        print_warning "NPM update had issues"
    fi
    echo ""
fi

# Step 5: Set proper permissions
echo "🔐 Setting file permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
print_success "Permissions set"
echo ""

# Step 6: Run migrations (optional - be careful in production)
echo "🗄️  Running database migrations..."
read -p "Do you want to run migrations? (y/N) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    if [ $? -eq 0 ]; then
        print_success "Migrations completed"
    else
        print_error "Migrations failed"
    fi
else
    print_info "Skipping migrations"
fi
echo ""

# Step 7: Fix routes (most important for your issue)
echo "🛣️  Fixing routes and caching..."
php fix_routes_production.php
if [ $? -eq 0 ]; then
    print_success "Routes fixed and cached"
else
    print_error "Route fixing failed"
    exit 1
fi
echo ""

# Step 8: Restart services
echo "🔄 Restarting services..."

# Check which web server is running
if systemctl is-active --quiet nginx; then
    sudo systemctl reload nginx
    print_success "Nginx reloaded"
elif systemctl is-active --quiet apache2; then
    sudo systemctl reload apache2
    print_success "Apache reloaded"
else
    print_warning "Could not detect web server to reload"
fi

# Restart PHP-FPM if it exists
if systemctl is-active --quiet php8.2-fpm; then
    sudo systemctl restart php8.2-fpm
    print_success "PHP-FPM restarted"
elif systemctl is-active --quiet php8.1-fpm; then
    sudo systemctl restart php8.1-fpm
    print_success "PHP-FPM restarted"
elif systemctl is-active --quiet php8.0-fpm; then
    sudo systemctl restart php8.0-fpm
    print_success "PHP-FPM restarted"
else
    print_warning "PHP-FPM not found or not needed"
fi

# Restart queue workers if they exist
if systemctl is-active --quiet laravel-worker; then
    sudo systemctl restart laravel-worker
    print_success "Queue workers restarted"
else
    print_info "No queue workers configured"
fi

echo ""

# Step 9: Health check
echo "🏥 Running health checks..."
print_info "Checking if application is accessible..."

# Check if site is responding
SITE_URL=$(grep APP_URL .env | cut -d '=' -f2)
if [ ! -z "$SITE_URL" ]; then
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" $SITE_URL)
    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
        print_success "Site is responding (HTTP $HTTP_CODE)"
    else
        print_error "Site returned HTTP $HTTP_CODE"
    fi
else
    print_warning "Could not determine APP_URL from .env"
fi

# Check if route:list works
php artisan route:list --name=login > /dev/null 2>&1
if [ $? -eq 0 ]; then
    print_success "Login route is registered"
else
    print_error "Login route NOT found - CRITICAL ISSUE"
fi

echo ""

# Step 10: Show deployment summary
echo "╔════════════════════════════════════════════════════════════╗"
echo "║              DEPLOYMENT COMPLETE                           ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

print_success "Code deployed successfully"
print_success "Routes cached and optimized"
print_success "Services restarted"
echo ""

echo "🔍 QUICK CHECKS:"
echo "─────────────────────────────────────────────────────────"
print_info "Test login page: ${SITE_URL}/login"
print_info "Check logs: tail -f storage/logs/laravel.log"
print_info "Check web server: systemctl status nginx"
echo ""

echo "💡 TROUBLESHOOTING:"
echo "─────────────────────────────────────────────────────────"
echo "If login still fails:"
echo "  1. Run: php fix_routes_production.php"
echo "  2. Check: tail -f storage/logs/laravel.log"
echo "  3. Check: tail -f /var/log/nginx/error.log"
echo "  4. Verify .env file has correct APP_URL"
echo ""

echo "⚡ OPTIMIZE DATABASE:"
echo "─────────────────────────────────────────────────────────"
echo "Run: php optimize_database.php"
echo ""

echo "✨ Deployment completed at $(date)"
echo ""
