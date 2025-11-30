@echo off
REM Export Optimized Database
REM This script exports the optimized database to import back to production

echo ========================================
echo EXPORTING OPTIMIZED DATABASE
echo ========================================
echo.

REM Set timestamp for filename
set timestamp=%date:~-4%%date:~3,2%%date:~0,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set timestamp=%timestamp: =0%

REM Database credentials
set DB_HOST=localhost
set DB_USER=root
set DB_PASS=
set DB_NAME=ebims1

REM Output file
set OUTPUT_FILE=database\backups\ebims1_optimized_%timestamp%.sql

echo Database: %DB_NAME%
echo Output: %OUTPUT_FILE%
echo.
echo Starting export...
echo.

REM Create backups directory if it doesn't exist
if not exist "database\backups" mkdir database\backups

REM Export database with optimizations
REM --single-transaction: For consistency without locking tables
REM --quick: Retrieve rows one at a time (less memory)
REM --routines: Include stored procedures/functions
REM --triggers: Include triggers
REM --events: Include scheduled events

c:\wamp64\bin\mysql\mysql8.3.0\bin\mysqldump.exe ^
  --host=%DB_HOST% ^
  --user=%DB_USER% ^
  --single-transaction ^
  --quick ^
  --routines ^
  --triggers ^
  --events ^
  --set-gtid-purged=OFF ^
  --default-character-set=utf8mb4 ^
  %DB_NAME% > %OUTPUT_FILE%

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo EXPORT SUCCESSFUL!
    echo ========================================
    echo.
    echo File: %OUTPUT_FILE%
    
    REM Get file size
    for %%A in (%OUTPUT_FILE%) do set size=%%~zA
    echo Size: %size% bytes
    echo.
    echo Next steps:
    echo 1. Compress the SQL file: 7z a %OUTPUT_FILE%.7z %OUTPUT_FILE%
    echo 2. Upload to your server
    echo 3. Import: mysql -u root -p ebims1 ^< backup.sql
    echo.
    echo ========================================
) else (
    echo.
    echo ========================================
    echo EXPORT FAILED!
    echo ========================================
    echo.
    echo Please check:
    echo 1. MySQL is running
    echo 2. Database credentials are correct
    echo 3. You have permissions to export
    echo.
)

pause
