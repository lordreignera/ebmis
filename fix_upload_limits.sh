#!/bin/bash
# Script to increase PHP upload limits on Digital Ocean / Linux servers
# Run this via SSH: bash fix_upload_limits.sh

echo "==================================================="
echo "Fixing PHP Upload Limits for Large Files"
echo "==================================================="
echo ""

# Find PHP version
PHP_VERSION=$(php -v | head -n 1 | cut -d ' ' -f 2 | cut -d '.' -f 1,2)
echo "Detected PHP Version: $PHP_VERSION"
echo ""

# Find php.ini files
PHP_CLI_INI="/etc/php/$PHP_VERSION/cli/php.ini"
PHP_FPM_INI="/etc/php/$PHP_VERSION/fpm/php.ini"
PHP_APACHE_INI="/etc/php/$PHP_VERSION/apache2/php.ini"

echo "Updating PHP configuration files..."
echo ""

# Function to update php.ini
update_php_ini() {
    local file=$1
    if [ -f "$file" ]; then
        echo "Updating: $file"
        
        # Backup original file
        sudo cp "$file" "$file.backup.$(date +%Y%m%d)"
        
        # Update upload_max_filesize
        sudo sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 100M/' "$file"
        
        # Update post_max_size
        sudo sed -i 's/^post_max_size = .*/post_max_size = 100M/' "$file"
        
        # Update max_execution_time
        sudo sed -i 's/^max_execution_time = .*/max_execution_time = 300/' "$file"
        
        # Update max_input_time
        sudo sed -i 's/^max_input_time = .*/max_input_time = 300/' "$file"
        
        # Update memory_limit
        sudo sed -i 's/^memory_limit = .*/memory_limit = 256M/' "$file"
        
        echo "✓ Updated successfully"
        echo ""
    else
        echo "⚠ File not found: $file"
        echo ""
    fi
}

# Update all php.ini files
update_php_ini "$PHP_CLI_INI"
update_php_ini "$PHP_FPM_INI"
update_php_ini "$PHP_APACHE_INI"

# Restart services
echo "Restarting PHP-FPM..."
if sudo systemctl restart php$PHP_VERSION-fpm 2>/dev/null; then
    echo "✓ PHP-FPM restarted"
else
    echo "⚠ PHP-FPM not running or restart failed"
fi

echo ""
echo "Restarting Apache (if installed)..."
if sudo systemctl restart apache2 2>/dev/null; then
    echo "✓ Apache restarted"
else
    echo "⚠ Apache not installed or restart failed"
fi

echo ""
echo "Restarting Nginx (if installed)..."
if sudo systemctl restart nginx 2>/dev/null; then
    echo "✓ Nginx restarted"
else
    echo "⚠ Nginx not installed or restart failed"
fi

echo ""
echo "==================================================="
echo "Verifying changes..."
echo "==================================================="
php -i | grep -E "upload_max_filesize|post_max_size|max_execution_time|memory_limit" | head -4

echo ""
echo "==================================================="
echo "✅ Done!"
echo "==================================================="
echo ""
echo "Your new limits:"
echo "  upload_max_filesize = 100M"
echo "  post_max_size = 100M"
echo "  max_execution_time = 300"
echo "  memory_limit = 256M"
echo ""
echo "You can now upload files up to 100MB!"
echo ""
