# ========================================# EBIMS Production Deployment Guide

# DIGITAL OCEAN DEPLOYMENT GUIDE

# ========================================## 📋 Pre-Deployment Checklist



## 🚨 IMMEDIATE FIX FOR "Route [login] not defined" ERROR### 1. Server Requirements

- PHP 8.1 or higher

### Quick Fix (Run on your Digital Ocean server):- MySQL 5.7+ or MariaDB 10.3+

- Composer

```bash- Node.js and NPM (for asset compilation)

cd /var/www/your-app-directory- Apache/Nginx web server

php fix_routes_production.php- SSL Certificate (for HTTPS)

```

### 2. Required PHP Extensions

This will:```

- Clear ALL caches (route, config, view, event)- OpenSSL (for Stanbic API encryption)

- Delete cached files manually- PDO

- Rebuild route cache- Mbstring

- Optimize for production- Tokenizer

- Verify login route exists- XML

- Ctype

---- JSON

- BCMath

## 📝 DEPLOYMENT WORKFLOW```



### Option 1: Manual Deployment (After Git Push)## 🚀 Deployment Steps



```bash### Step 1: Upload Files to Server

# SSH into your Digital Ocean droplet```bash

ssh root@your-server-ip# Upload your Laravel project to the server

# Exclude these directories (they will be generated):

# Navigate to your app# - vendor/

cd /var/www/your-app-directory# - node_modules/

# - storage/logs/*

# Pull latest code# - bootstrap/cache/*

git pull origin master```



# Run the comprehensive fix### Step 2: Set Up Environment File

php fix_routes_production.php```bash

# On your production server:

# Restart servicescd /path/to/your/project

sudo systemctl reload nginx

sudo systemctl restart php8.2-fpm# Copy the production environment file

```cp .env.production.example .env



### Option 2: Automated Deployment Script# Edit the .env file with your production values

nano .env

```bash```

# Make deploy script executable (first time only)

chmod +x deploy.sh### Step 3: Update These CRITICAL Values in .env



# Run deployment#### A. Application Settings

bash deploy.sh```env

```APP_ENV=production

APP_DEBUG=false

---APP_URL=https://yourdomain.com

```

## 🐛 WHY THE LOGIN ROUTE KEEPS BREAKING

#### B. Database Configuration

### Root Causes:```env

DB_CONNECTION=mysql

1. **Route Closures in Cache**DB_HOST=127.0.0.1

   - Laravel can't cache routes that have closuresDB_DATABASE=your_production_database_name

   - Your `web.php` has several route closures (lines 7-9, 31-51, 68-71, 78-80)DB_USERNAME=your_database_username

DB_PASSWORD=your_secure_database_password

2. **Cache Persistence Issues**```

   - Digital Ocean may be clearing cache on restart

   - Memory limits causing cache failures#### C. Stanbic FlexiPay (Mobile Money) - ALREADY CONFIGURED

```env

3. **File Permissions**# These values are already set in the .env.production.example

   - `bootstrap/cache` directory not writable# DO NOT CHANGE unless Stanbic provides new credentials

   - Web server can't write cache filesSTANBIC_CLIENT_ID=d9b777335bde2e6d25db4dd0412de846

STANBIC_CLIENT_SECRET=3ee79ec68493ecc0dca4d07a87ea71f0

### Permanent Solutions:STANBIC_MERCHANT_CODE=243575

STANBIC_CLIENT_NAME=EBIMSPRD

#### Solution 1: Remove Route Closures (RECOMMENDED)STANBIC_ENABLED=true

STANBIC_TEST_MODE=false

Convert your route closures to controller methods:```



**Current Problem (web.php line 7-9):**### Step 4: Install Dependencies and Generate Key

```php```bash

Route::get('/', function () {# Install PHP dependencies

    return redirect()->route('login');composer install --no-dev --optimize-autoloader

});

```# Generate application key

php artisan key:generate

**Fix: Create HomeController:**

```php# Set correct permissions

// app/Http/Controllers/HomeController.phpchmod -R 755 storage bootstrap/cache

<?phpchown -R www-data:www-data storage bootstrap/cache

namespace App\Http\Controllers;```



class HomeController extends Controller### Step 5: Database Setup

{```bash

    public function index()# Import your database

    {mysql -u username -p database_name < your_database_backup.sql

        return redirect()->route('login');

    }# OR run migrations if starting fresh

}php artisan migrate --force

```

# Run seeders if needed

**Update routes:**php artisan db:seed --force

```php```

// routes/web.php

Route::get('/', [HomeController::class, 'index']);### Step 6: Build Frontend Assets

``````bash

# Install Node dependencies

#### Solution 2: Disable Route Cachingnpm install



In `fix_routes_production.php`, comment out line 119:# Build production assets

```phpnpm run build

// Artisan::call('route:cache');  // Disable if using closures```

```

### Step 7: Optimize Laravel

#### Solution 3: Fix File Permissions```bash

# Clear and cache configuration

```bashphp artisan config:cache

# On Digital Oceanphp artisan route:cache

cd /var/www/your-appphp artisan view:cache

chmod -R 775 storage

chmod -R 775 bootstrap/cache# Clear any old cached tokens

chown -R www-data:www-data storagephp artisan cache:clear

chown -R www-data:www-data bootstrap/cache```

```

### Step 8: Configure Web Server

---

#### For Apache (.htaccess already included)

## ⚡ PERFORMANCE OPTIMIZATION```apache

<VirtualHost *:443>

### 1. Database Optimization    ServerName yourdomain.com

    DocumentRoot /path/to/ebims/public

```bash

# Run the database optimizer    <Directory /path/to/ebims/public>

php optimize_database.php        AllowOverride All

```        Require all granted

    </Directory>

This will:

- Optimize all tables (like VACUUM in PostgreSQL)    SSLEngine on

- Analyze tables for query planning    SSLCertificateFile /path/to/certificate.crt

- Clean old sessions (older than 30 days)    SSLCertificateKeyFile /path/to/private.key

- Clean failed jobs (older than 7 days)</VirtualHost>

- Check for missing indexes```

- Provide optimization recommendations

#### For Nginx

### 2. Laravel Application Optimization```nginx

server {

```bash    listen 443 ssl;

# Cache everything    server_name yourdomain.com;

php artisan config:cache    root /path/to/ebims/public;

php artisan route:cache    # Only if NO closures in routes

php artisan view:cache    index index.php;

php artisan event:cache

php artisan optimize    ssl_certificate /path/to/certificate.crt;

```    ssl_certificate_key /path/to/private.key;



### 3. Enable Redis (Highly Recommended)    location / {

        try_files $uri $uri/ /index.php?$query_string;

**Install Redis:**    }

```bash

sudo apt install redis-server    location ~ \.php$ {

sudo systemctl enable redis-server        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;

sudo systemctl start redis-server        fastcgi_index index.php;

```        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;

        include fastcgi_params;

**Update .env:**    }

```env}

CACHE_DRIVER=redis```

SESSION_DRIVER=redis

QUEUE_CONNECTION=redis## ✅ Post-Deployment Verification



REDIS_HOST=127.0.0.1### 1. Test Mobile Money Integration

REDIS_PASSWORD=null```bash

REDIS_PORT=6379# Check if Stanbic API is reachable

```php artisan tinker



**Install PHP Redis:**# In tinker:

```bash$service = app(\App\Services\StanbicFlexiPayService::class);

composer require predis/predis$result = $service->testConnection();

```dd($result);

```

### 4. Enable OPcache

Expected output:

**Check if enabled:**```php

```bash[

php -i | grep opcache    "connection" => true,

```    "message" => "Successfully connected to Stanbic FlexiPay API",

    "token_generated" => true

**Enable in php.ini:**]

```ini```

opcache.enable=1

opcache.memory_consumption=256### 2. Verify OAuth Token Caching

opcache.interned_strings_buffer=16```bash

opcache.max_accelerated_files=10000# Check logs after making a mobile money payment

opcache.validate_timestamps=0  # Set to 0 in productiontail -f storage/logs/laravel.log

opcache.revalidate_freq=0

opcache.save_comments=1# You should see:

```# - "Requesting new OAuth token from Stanbic" (first request)

# - "Using cached OAuth token" (subsequent requests within 15 minutes)

**Restart PHP-FPM:**# - NO "401 Unauthorized" errors

```bash```

sudo systemctl restart php8.2-fpm

```### 3. Test a Small Payment

1. Log into the system

### 5. MySQL/MariaDB Optimization2. Navigate to a loan with unpaid schedules

3. Initiate a small mobile money payment (e.g., 1000 UGX)

**Edit MySQL config:**4. Verify the payment completes successfully

```bash

sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf### 4. Monitor Logs

``````bash

# Watch for any errors

**Add these settings:**tail -f storage/logs/laravel.log | grep -i error

```ini```

[mysqld]

innodb_buffer_pool_size = 1G        # 70% of available RAM## 🔒 Security Checklist

innodb_log_file_size = 256M

innodb_flush_method = O_DIRECT- [ ] APP_DEBUG is set to `false`

max_connections = 200- [ ] APP_ENV is set to `production`

query_cache_type = 1- [ ] Database credentials are secure

query_cache_size = 64M- [ ] .env file is NOT in git repository

query_cache_limit = 2M- [ ] .env file permissions are 600 or 640

tmp_table_size = 128M- [ ] SSL certificate is installed and valid

max_heap_table_size = 128M- [ ] storage/ and bootstrap/cache/ directories are writable

table_open_cache = 4000- [ ] Logs are being rotated (not filling disk)

innodb_file_per_table = 1

```## 🔧 Troubleshooting



**Restart MySQL:**### Mobile Money Payments Failing (401 Unauthorized)

```bash

sudo systemctl restart mysql**Symptom:** "Collection failed" error, logs show 401 from Stanbic

```

**Solution:** Clear the cached OAuth token

---```bash

php artisan cache:forget stanbic_oauth_token

## 🔄 AUTOMATE DEPLOYMENTS```



### Create a Git Post-Receive Hook### Token Expires Too Quickly



**On your Digital Ocean server:****Check:** Verify the TTL in config/stanbic_flexipay.php

```php

```bash'token_cache' => [

# Create bare repository    'ttl' => 900, // Should be 900 seconds (15 minutes)

mkdir -p /var/repo/ebims.git],

cd /var/repo/ebims.git```

git init --bare

### Slow API Responses

# Create post-receive hook

nano hooks/post-receive**Check:** Network connectivity to Stanbic servers

``````bash

curl -I https://gateway.apps.platform.stanbicbank.co.ug

**Add this script:**```

```bash

#!/bin/bash## 📞 Support Contacts

TARGET="/var/www/ebims"

GIT_DIR="/var/repo/ebims.git"- **Stanbic FlexiPay Support:** Check your Stanbic contract for support contacts

- **Server Issues:** Contact your hosting provider

while read oldrev newrev ref- **Application Issues:** Check logs in `storage/logs/laravel.log`

do

    if [[ $ref =~ .*/master$ ]]; then## 🔄 Regular Maintenance

        echo "Deploying master branch to production..."

        git --work-tree=$TARGET --git-dir=$GIT_DIR checkout -f### Daily

        cd $TARGET- Monitor error logs

        php fix_routes_production.php- Check disk space

        sudo systemctl reload nginx

        sudo systemctl restart php8.2-fpm### Weekly

        echo "Deployment complete!"- Clear old logs (keep last 7 days)

    fi```bash

donephp artisan log:clear --keep-last=7

``````



**Make executable:**### Monthly

```bash- Review mobile money transaction success rates

chmod +x hooks/post-receive- Check database size and optimize if needed

``````bash

php artisan optimize:clear

**On your local machine, add remote:**php artisan optimize

```bash```

git remote add production ssh://root@your-server-ip/var/repo/ebims.git

```## 📝 Important Notes



**Deploy:**1. **OAuth Token Cache:** The system caches OAuth tokens for 15 minutes. This is optimal for the Stanbic API which expires tokens after ~24 minutes.

```bash

git push production master2. **Loan Status Updates:** When the last schedule of a loan is paid, the loan status automatically changes from 2 (Disbursed) to 3 (Completed).

```

3. **Interest Calculation:** Daily loans use declining interest formula that matches the bimsadmin system exactly.

---

4. **Database Backups:** Set up automated daily backups of your production database.

## 📊 MONITORING & LOGS

5. **SSL Required:** Mobile money API requires HTTPS. Ensure your SSL certificate is valid.

### Check Application Logs
```bash
tail -f /var/www/ebims/storage/logs/laravel.log
```

### Check Web Server Logs
```bash
# Nginx
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log

# Apache
tail -f /var/log/apache2/error.log
```

### Check PHP-FPM Logs
```bash
tail -f /var/log/php8.2-fpm.log
```

### Check MySQL Slow Queries
```bash
tail -f /var/log/mysql/mysql-slow.log
```

### Monitor System Resources
```bash
htop
df -h
free -m
```

---

## 🔧 CRON JOBS

**Add to crontab:**
```bash
crontab -e
```

**Add these lines:**
```bash
# Laravel Scheduler
* * * * * cd /var/www/ebims && php artisan schedule:run >> /dev/null 2>&1

# Weekly Database Optimization (Sunday 2 AM)
0 2 * * 0 cd /var/www/ebims && php optimize_database.php >> /var/log/db_optimize.log 2>&1

# Daily Route Cache Clear (Daily 3 AM)
0 3 * * * cd /var/www/ebims && php artisan route:clear && php artisan route:cache >> /dev/null 2>&1
```

---

## 🆘 TROUBLESHOOTING

### If Login Still Fails:

1. **Check Route Registration:**
```bash
php artisan route:list --name=login
```

2. **Check .env File:**
```bash
cat .env | grep APP_URL
# Should match your domain: https://yourdomain.com
```

3. **Check Fortify Installation:**
```bash
composer show | grep fortify
php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"
```

4. **Verify Web Server Config:**
```bash
# Nginx
sudo nginx -t
cat /etc/nginx/sites-available/your-site

# Apache
sudo apache2ctl -t
cat /etc/apache2/sites-available/your-site.conf
```

5. **Check File Ownership:**
```bash
ls -la storage/
ls -la bootstrap/cache/
# Should be owned by www-data
```

---

## 🚀 PERFORMANCE BENCHMARKS

After optimization, you should see:
- **Page load time:** < 500ms
- **Database queries:** < 50ms average
- **Memory usage:** < 512MB
- **CPU usage:** < 30% average

---

## 📞 SUPPORT

If issues persist after following this guide:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check web server error logs
3. Verify all file permissions
4. Ensure .env file is correct
5. Test with: `php artisan route:list`

---

## ✅ DEPLOYMENT CHECKLIST

- [ ] SSH access to Digital Ocean server
- [ ] Git repository configured
- [ ] .env file exists and is correct
- [ ] File permissions set (775 for storage/bootstrap)
- [ ] Composer dependencies installed
- [ ] Database migrations run
- [ ] Route cache cleared and rebuilt
- [ ] Web server configured correctly
- [ ] SSL certificate installed (recommended)
- [ ] Redis installed (recommended)
- [ ] OPcache enabled
- [ ] Database optimized
- [ ] Cron jobs configured
- [ ] Monitoring setup

---

**Last Updated:** November 21, 2025
**Version:** 1.0
