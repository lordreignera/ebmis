@echo off
echo ========================================
echo   Upload Database to DigitalOcean
echo ========================================
echo.

REM Replace these with your actual server details
set SERVER_IP=your-server-ip
set SERVER_USER=root
set DB_NAME=ebims1

echo Step 1: Uploading SQL file to server...
echo.
echo Command to run:
echo scp ebimson43_fixed.sql %SERVER_USER%@%SERVER_IP%:/root/
echo.
echo Step 2: After upload, SSH into server and run:
echo ssh %SERVER_USER%@%SERVER_IP%
echo.
echo Step 3: On the server, import database:
echo mysql -u root -p %DB_NAME% ^< /root/ebimson43_fixed.sql
echo.
echo Step 4: Update Laravel application:
echo cd /var/www/html
echo git pull origin master
echo php artisan config:clear
echo php artisan cache:clear
echo.
echo ========================================
echo Ready to upload?
echo ========================================
echo.
echo Please update SERVER_IP in this file first!
echo Then run each command manually.
echo.
pause
