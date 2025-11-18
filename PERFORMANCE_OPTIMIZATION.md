# Performance Optimization Guide for Production

## Current Server Specs
- **RAM**: 1 GB
- **CPU**: 1 Shared vCPU
- **Bandwidth**: 150 GB
- **Issue**: System is very slow compared to local environment

## Immediate Actions (Do These First!)

### 1. Enable Laravel Caching
Run these commands on your server to cache configuration and routes:

```bash
cd /path/to/ebims

# Cache configuration files
php artisan config:cache

# Cache routes (speeds up routing significantly)
php artisan route:cache

# Cache views (pre-compile Blade templates)
php artisan view:cache

# Optimize class loading
composer install --optimize-autoloader --no-dev
php artisan optimize
```

### 2. Enable OpCache (PHP)
Edit your `php.ini` file on the server:

```ini
# Find these settings and enable them:
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

After editing, restart PHP-FPM or Apache:
```bash
sudo systemctl restart php8.1-fpm  # or php-fpm
sudo systemctl restart apache2     # if using Apache
# OR
sudo systemctl restart nginx       # if using Nginx
```

### 3. Optimize Database Queries

Add indexes to frequently queried columns. Run this on production:

```sql
-- Add indexes to improve query performance
ALTER TABLE members ADD INDEX idx_member_type (member_type);
ALTER TABLE members ADD INDEX idx_group_id (group_id);
ALTER TABLE members ADD INDEX idx_verified (verified);
ALTER TABLE members ADD INDEX idx_soft_delete (soft_delete);
ALTER TABLE members ADD INDEX idx_branch_id (branch_id);

ALTER TABLE personal_loans ADD INDEX idx_user_id (user_id);
ALTER TABLE personal_loans ADD INDEX idx_status (status);
ALTER TABLE personal_loans ADD INDEX idx_verified (verified);
ALTER TABLE personal_loans ADD INDEX idx_branch_id (branch_id);

ALTER TABLE groups ADD INDEX idx_verified (verified);
ALTER TABLE groups ADD INDEX idx_branch_id (branch_id);

ALTER TABLE transactions ADD INDEX idx_member_id (member_id);
ALTER TABLE transactions ADD INDEX idx_transaction_date (transaction_date);
ALTER TABLE transactions ADD INDEX idx_type (type);
```

### 4. Enable Query Caching (Add to .env)

```env
# Cache settings (add to your .env file)
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

# Or if you have Redis available:
# CACHE_DRIVER=redis
# SESSION_DRIVER=redis
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379
```

### 5. Reduce Page Load - Use Pagination Everywhere

Check that all your data tables use pagination (already done for members and groups).

### 6. Enable GZIP Compression

**For Apache** - Add to `.htaccess`:
```apache
# Enable GZIP compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

**For Nginx** - Add to nginx config:
```nginx
gzip on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript;
gzip_min_length 1000;
```

## Medium-Term Solutions

### 7. Use CDN for Assets
Move your CSS, JS, and images to a CDN (Cloudflare is free):
- Sign up for Cloudflare
- Add your domain
- Enable CDN and caching

### 8. Optimize Images
```bash
# Install image optimization tools
sudo apt-get install jpegoptim optipng pngquant

# Optimize existing images
find storage/app/public -name "*.jpg" -exec jpegoptim --strip-all {} \;
find storage/app/public -name "*.png" -exec optipng {} \;
```

### 9. Defer JavaScript Loading
Add `defer` to non-critical scripts in your layout:
```html
<script src="/js/app.js" defer></script>
```

### 10. Use Eager Loading (Prevent N+1 Queries)
Already done in most places, but verify controllers use `with()`:
```php
// Good - loads relationships in one query
$members = Member::with(['country', 'branch', 'group'])->paginate(20);

// Bad - causes N+1 queries
$members = Member::paginate(20);
// Then accessing $member->country causes extra queries
```

## Server Configuration Improvements

### 11. Increase PHP Memory Limit
Edit `php.ini`:
```ini
memory_limit = 256M  # Increase from default 128M
max_execution_time = 60
upload_max_filesize = 10M
post_max_size = 10M
```

### 12. Configure MySQL for Low Memory

Edit `/etc/mysql/my.cnf` or `/etc/mysql/mysql.conf.d/mysqld.cnf`:
```ini
[mysqld]
# Optimize for 1GB RAM server
innodb_buffer_pool_size = 256M  # 25% of RAM
innodb_log_file_size = 64M
query_cache_size = 32M
query_cache_limit = 2M
max_connections = 50  # Reduce if you have few concurrent users
table_open_cache = 256
thread_cache_size = 8
```

Restart MySQL:
```bash
sudo systemctl restart mysql
```

## Long-Term Solutions

### 13. Upgrade Server Resources
**Recommended for your application**:
- **RAM**: 2 GB minimum (currently 1 GB)
- **CPU**: 2 vCPUs (currently 1 shared)
- Cost: ~$18-24/month on DigitalOcean

### 14. Add Redis for Caching
```bash
# Install Redis
sudo apt-get install redis-server

# Update .env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 15. Enable Queue Workers for Background Jobs
```bash
# Run queue worker as a service
php artisan queue:work --daemon

# Or use Supervisor to keep it running
sudo apt-get install supervisor
```

## Monitoring & Testing

### Check Current Performance
```bash
# Check memory usage
free -h

# Check MySQL slow queries
sudo mysqldumpslow /var/log/mysql/mysql-slow.log

# Check PHP-FPM status
sudo systemctl status php8.1-fpm

# Monitor real-time
htop
```

### Laravel Debug Bar (Development Only!)
**Never enable in production**, but useful locally:
```bash
composer require barryvdh/laravel-debugbar --dev
```

## Quick Wins Summary (Do These Now!)

1. ✅ Run caching commands (config, route, view cache)
2. ✅ Enable OpCache in php.ini
3. ✅ Add database indexes
4. ✅ Enable GZIP compression
5. ✅ Set CACHE_DRIVER=file in .env

**Expected improvement**: 30-50% faster page loads

## When to Upgrade Server

If after optimizations you still have issues:
- More than 50 concurrent users
- Database size > 1GB
- Consistent high memory usage (>90%)

**Recommended upgrade**: $18/mo droplet (2GB RAM, 2 vCPUs)

## Performance Checklist

- [ ] Caching enabled (config, route, view)
- [ ] OpCache enabled
- [ ] Database indexes added
- [ ] GZIP compression enabled
- [ ] Images optimized
- [ ] CDN configured (optional)
- [ ] Redis installed (optional)
- [ ] Server upgraded if needed

---

## Test Performance After Changes

Visit your site and check:
1. Homepage load time
2. Member list page load time
3. Loan disbursements page
4. Database query time in logs

Target: < 2 seconds for most pages
