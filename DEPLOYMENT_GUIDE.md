# EBIMS Production Deployment Guide

## 📋 Pre-Deployment Checklist

### 1. Server Requirements
- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Node.js and NPM (for asset compilation)
- Apache/Nginx web server
- SSL Certificate (for HTTPS)

### 2. Required PHP Extensions
```
- OpenSSL (for Stanbic API encryption)
- PDO
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
```

## 🚀 Deployment Steps

### Step 1: Upload Files to Server
```bash
# Upload your Laravel project to the server
# Exclude these directories (they will be generated):
# - vendor/
# - node_modules/
# - storage/logs/*
# - bootstrap/cache/*
```

### Step 2: Set Up Environment File
```bash
# On your production server:
cd /path/to/your/project

# Copy the production environment file
cp .env.production.example .env

# Edit the .env file with your production values
nano .env
```

### Step 3: Update These CRITICAL Values in .env

#### A. Application Settings
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

#### B. Database Configuration
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_production_database_name
DB_USERNAME=your_database_username
DB_PASSWORD=your_secure_database_password
```

#### C. Stanbic FlexiPay (Mobile Money) - ALREADY CONFIGURED
```env
# These values are already set in the .env.production.example
# DO NOT CHANGE unless Stanbic provides new credentials
STANBIC_CLIENT_ID=d9b777335bde2e6d25db4dd0412de846
STANBIC_CLIENT_SECRET=3ee79ec68493ecc0dca4d07a87ea71f0
STANBIC_MERCHANT_CODE=243575
STANBIC_CLIENT_NAME=EBIMSPRD
STANBIC_ENABLED=true
STANBIC_TEST_MODE=false
```

### Step 4: Install Dependencies and Generate Key
```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Generate application key
php artisan key:generate

# Set correct permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Step 5: Database Setup
```bash
# Import your database
mysql -u username -p database_name < your_database_backup.sql

# OR run migrations if starting fresh
php artisan migrate --force

# Run seeders if needed
php artisan db:seed --force
```

### Step 6: Build Frontend Assets
```bash
# Install Node dependencies
npm install

# Build production assets
npm run build
```

### Step 7: Optimize Laravel
```bash
# Clear and cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear any old cached tokens
php artisan cache:clear
```

### Step 8: Configure Web Server

#### For Apache (.htaccess already included)
```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /path/to/ebims/public

    <Directory /path/to/ebims/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
</VirtualHost>
```

#### For Nginx
```nginx
server {
    listen 443 ssl;
    server_name yourdomain.com;
    root /path/to/ebims/public;

    index index.php;

    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## ✅ Post-Deployment Verification

### 1. Test Mobile Money Integration
```bash
# Check if Stanbic API is reachable
php artisan tinker

# In tinker:
$service = app(\App\Services\StanbicFlexiPayService::class);
$result = $service->testConnection();
dd($result);
```

Expected output:
```php
[
    "connection" => true,
    "message" => "Successfully connected to Stanbic FlexiPay API",
    "token_generated" => true
]
```

### 2. Verify OAuth Token Caching
```bash
# Check logs after making a mobile money payment
tail -f storage/logs/laravel.log

# You should see:
# - "Requesting new OAuth token from Stanbic" (first request)
# - "Using cached OAuth token" (subsequent requests within 15 minutes)
# - NO "401 Unauthorized" errors
```

### 3. Test a Small Payment
1. Log into the system
2. Navigate to a loan with unpaid schedules
3. Initiate a small mobile money payment (e.g., 1000 UGX)
4. Verify the payment completes successfully

### 4. Monitor Logs
```bash
# Watch for any errors
tail -f storage/logs/laravel.log | grep -i error
```

## 🔒 Security Checklist

- [ ] APP_DEBUG is set to `false`
- [ ] APP_ENV is set to `production`
- [ ] Database credentials are secure
- [ ] .env file is NOT in git repository
- [ ] .env file permissions are 600 or 640
- [ ] SSL certificate is installed and valid
- [ ] storage/ and bootstrap/cache/ directories are writable
- [ ] Logs are being rotated (not filling disk)

## 🔧 Troubleshooting

### Mobile Money Payments Failing (401 Unauthorized)

**Symptom:** "Collection failed" error, logs show 401 from Stanbic

**Solution:** Clear the cached OAuth token
```bash
php artisan cache:forget stanbic_oauth_token
```

### Token Expires Too Quickly

**Check:** Verify the TTL in config/stanbic_flexipay.php
```php
'token_cache' => [
    'ttl' => 900, // Should be 900 seconds (15 minutes)
],
```

### Slow API Responses

**Check:** Network connectivity to Stanbic servers
```bash
curl -I https://gateway.apps.platform.stanbicbank.co.ug
```

## 📞 Support Contacts

- **Stanbic FlexiPay Support:** Check your Stanbic contract for support contacts
- **Server Issues:** Contact your hosting provider
- **Application Issues:** Check logs in `storage/logs/laravel.log`

## 🔄 Regular Maintenance

### Daily
- Monitor error logs
- Check disk space

### Weekly
- Clear old logs (keep last 7 days)
```bash
php artisan log:clear --keep-last=7
```

### Monthly
- Review mobile money transaction success rates
- Check database size and optimize if needed
```bash
php artisan optimize:clear
php artisan optimize
```

## 📝 Important Notes

1. **OAuth Token Cache:** The system caches OAuth tokens for 15 minutes. This is optimal for the Stanbic API which expires tokens after ~24 minutes.

2. **Loan Status Updates:** When the last schedule of a loan is paid, the loan status automatically changes from 2 (Disbursed) to 3 (Completed).

3. **Interest Calculation:** Daily loans use declining interest formula that matches the bimsadmin system exactly.

4. **Database Backups:** Set up automated daily backups of your production database.

5. **SSL Required:** Mobile money API requires HTTPS. Ensure your SSL certificate is valid.
