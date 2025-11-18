#!/bin/bash

# Performance Optimization Script for EBIMS
# Run this on your DigitalOcean server after deployment

echo "=========================================="
echo "EBIMS Performance Optimization Script"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Clear all caches first
echo -e "${YELLOW}Step 1: Clearing old caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Caches cleared${NC}"
echo ""

# 2. Optimize composer autoloader
echo -e "${YELLOW}Step 2: Optimizing Composer autoloader...${NC}"
composer install --optimize-autoloader --no-dev
echo -e "${GREEN}✓ Composer optimized${NC}"
echo ""

# 3. Cache configuration
echo -e "${YELLOW}Step 3: Caching configuration files...${NC}"
php artisan config:cache
echo -e "${GREEN}✓ Config cached${NC}"
echo ""

# 4. Cache routes
echo -e "${YELLOW}Step 4: Caching routes...${NC}"
php artisan route:cache
echo -e "${GREEN}✓ Routes cached${NC}"
echo ""

# 5. Cache views
echo -e "${YELLOW}Step 5: Caching views...${NC}"
php artisan view:cache
echo -e "${GREEN}✓ Views cached${NC}"
echo ""

# 6. Optimize application
echo -e "${YELLOW}Step 6: Running Laravel optimize...${NC}"
php artisan optimize
echo -e "${GREEN}✓ Application optimized${NC}"
echo ""

# 7. Set correct permissions
echo -e "${YELLOW}Step 7: Setting correct permissions...${NC}"
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

# 8. Show memory usage
echo -e "${YELLOW}Current Server Memory:${NC}"
free -h
echo ""

# 9. Show disk usage
echo -e "${YELLOW}Current Disk Usage:${NC}"
df -h /
echo ""

echo "=========================================="
echo -e "${GREEN}✓ Optimization Complete!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Check if OpCache is enabled: php -i | grep opcache"
echo "2. Add database indexes (see PERFORMANCE_OPTIMIZATION.md)"
echo "3. Enable GZIP compression in web server"
echo "4. Monitor performance with: htop"
echo ""
