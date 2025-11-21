@echo off
REM Backup local ebims1 database before importing production data
REM This creates a full backup including structure and data

echo ============================================================
echo BACKING UP LOCAL EBIMS1 DATABASE
echo ============================================================
echo.

REM Set variables
set TIMESTAMP=%date:~-4%%date:~3,2%%date:~0,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set BACKUP_FILE=ebims1_local_backup_%TIMESTAMP%.sql
set DB_NAME=ebims1
set DB_USER=root

echo Backup file: %BACKUP_FILE%
echo Database: %DB_NAME%
echo.

REM Check if mysqldump exists
where mysqldump >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: mysqldump not found in PATH!
    echo Please add MySQL bin directory to your PATH
    echo Example: C:\wamp64\bin\mysql\mysql8.0.x\bin
    pause
    exit /b 1
)

echo Creating full database backup...
echo.

REM Full backup with structure and data
mysqldump -u %DB_USER% -p %DB_NAME% > %BACKUP_FILE%

if %errorlevel% equ 0 (
    echo.
    echo ============================================================
    echo SUCCESS: Backup completed!
    echo ============================================================
    echo.
    echo Backup saved to: %BACKUP_FILE%
    echo File size:
    dir %BACKUP_FILE% | find "%BACKUP_FILE%"
    echo.
    echo ============================================================
    echo KEY TABLES BACKED UP:
    echo ============================================================
    echo   - personal_loans (all statuses)
    echo   - group_loans
    echo   - members
    echo   - loan_schedules
    echo   - repayments
    echo   - disbursements
    echo   - products
    echo   - branches
    echo.
    echo ============================================================
    echo NEXT STEPS:
    echo ============================================================
    echo.
    echo 1. Keep this backup file safe
    echo 2. Export production database from online server
    echo 3. Import production database:
    echo    mysql -u root -p ebims1 ^< production_backup.sql
    echo.
    echo 4. If you need to restore local backup:
    echo    mysql -u root -p ebims1 ^< %BACKUP_FILE%
    echo.
) else (
    echo.
    echo ============================================================
    echo ERROR: Backup failed!
    echo ============================================================
    echo.
    echo Please check:
    echo   - MySQL is running
    echo   - Database 'ebims1' exists
    echo   - Correct MySQL password
    echo.
)

pause
