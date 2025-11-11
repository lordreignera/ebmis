# Quick Fix for Foreign Key Import Errors

## Problem
MySQL is complaining about incompatible foreign key constraints when importing the SQL dump.

## Solution
The SQL dump uses the **old database structure**. Don't run migrations after import!

### On Your Server (DigitalOcean):

```bash
# 1. Drop the database if it exists (careful!)
mysql -u root -p -e "DROP DATABASE IF EXISTS ebims1;"

# 2. Create fresh database
mysql -u root -p -e "CREATE DATABASE ebims1 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Import the SQL dump (NO migrations will run)
mysql -u root -p ebims1 < /var/www/html/admin/ebimson43_fixed.sql

# 4. Clear Laravel caches
cd /var/www/html
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 5. DO NOT RUN: php artisan migrate
#    Your database already has all tables from the SQL import!
```

## Important Notes

1. **Don't run migrations** - The SQL dump already contains all tables with data
2. **Don't run seeders** - The SQL dump already contains all data
3. **Just import and use** - The application will work with the imported structure

## If You Want Fresh Laravel Structure Instead

If you want to use Laravel migrations instead of the old structure:

```bash
# 1. Create fresh database
mysql -u root -p -e "DROP DATABASE IF EXISTS ebims1;"
mysql -u root -p -e "CREATE DATABASE ebims1 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Run Laravel migrations (creates fresh structure)
cd /var/www/html
php artisan migrate:fresh

# 3. Then you'd need to import ONLY the data (not structure) from old database
#    This is more complex and requires a data-only export
```

## Recommended Approach

**Use the SQL import** - It's faster, simpler, and preserves all your data exactly as it is.

The application code is already compatible with the old database structure (we disabled timestamps, fixed column mappings, etc.).

Just import and go! 🚀
