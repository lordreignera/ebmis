#!/bin/bash

# Production Cache Clear Script for ebmis.emuria.net

echo "================================"
echo "Clearing Production Caches"
echo "================================"
echo ""

# Navigate to project directory
cd /path/to/ebims || exit

echo "1. Clearing view cache..."
php artisan view:clear

echo "2. Clearing config cache..."
php artisan config:clear

echo "3. Clearing route cache..."
php artisan route:clear

echo "4. Clearing application cache..."
php artisan cache:clear

echo "5. Clearing compiled classes..."
php artisan clear-compiled

echo "6. Optimizing autoloader..."
composer dump-autoload --optimize

echo ""
echo "================================"
echo "✅ All caches cleared!"
echo "================================"
echo ""
echo "Now test: http://ebmis.emuria.net/admin/repayments?type=personal"
