@echo off
REM EBIMS Transaction Checker
REM This script checks pending mobile money transactions and auto-processes successful payments
REM Run this every 5 minutes using Windows Task Scheduler

cd /d "%~dp0"
php artisan transactions:check >> storage\logs\check_transactions.log 2>&1
