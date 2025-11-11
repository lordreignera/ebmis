# Database Import Instructions for DigitalOcean

## Option 1: Using phpMyAdmin (Recommended for smaller files)

1. Login to your server's phpMyAdmin
2. Select your database from the left sidebar
3. Click on the **"Import"** tab (NOT the SQL tab)
4. Click **"Choose File"** and select `ebimson43_fixed.sql`
5. Scroll down and click **"Import"**

**Note:** If file is too large, phpMyAdmin may timeout. Use Option 2 instead.

## Option 2: Using Command Line (Recommended for large files)

### Step 1: Upload the SQL file to your server

```bash
# From your local computer (PowerShell/CMD)
scp ebimson43_fixed.sql root@your-server-ip:/root/
```

### Step 2: SSH into your server

```bash
ssh root@your-server-ip
```

### Step 3: Import the database

```bash
# Navigate to where you uploaded the file
cd /root

# Import the database (replace 'your_database_name' with actual DB name)
mysql -u root -p your_database_name < ebimson43_fixed.sql

# Or if you need to create the database first:
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS ebims1;"
mysql -u root -p ebims1 < ebimson43_fixed.sql
```

### Step 4: Update your Laravel application

```bash
cd /var/www/html
git pull origin master
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Step 5: Set correct permissions

```bash
cd /var/www/html
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## Option 3: Using MySQL Workbench

1. Open MySQL Workbench
2. Connect to your DigitalOcean server
3. Go to **Server** → **Data Import**
4. Select **"Import from Self-Contained File"**
5. Browse and select `ebimson43_fixed.sql`
6. Click **"Start Import"**

## Troubleshooting

### If you get "packet too large" error:

```bash
# SSH into server
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

# Add these lines under [mysqld]:
max_allowed_packet=256M
net_buffer_length=1M

# Save and restart MySQL
sudo systemctl restart mysql
```

### If you get "unknown database" error:

```bash
# Create the database first
mysql -u root -p -e "CREATE DATABASE ebims1 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Check if import was successful:

```bash
mysql -u root -p ebims1 -e "SHOW TABLES;"
mysql -u root -p ebims1 -e "SELECT COUNT(*) FROM users;"
```

## Database Credentials

Make sure your `.env` file on the server has correct database settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ebims1
DB_USERNAME=root
DB_PASSWORD=your_password
```

## After Import

Test your application:
- Visit your domain
- Try logging in with existing users
- Check if all data is present
- Verify loans, members, transactions appear correctly

## File Information

- **Original File:** ebimson43.sql
- **Fixed File:** ebimson43_fixed.sql (with backticks for `groups` table)
- **Size:** ~1.7 MB
- **Tables:** All EBIMS tables with data
- **Compatible with:** MySQL 5.7+, MySQL 8.x
