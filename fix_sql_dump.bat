@echo off
REM ============================================
REM SQL Dump Fixer for MySQL Import
REM Fixes reserved keywords and syntax issues
REM ============================================

echo.
echo ========================================
echo   SQL Dump Fixer for Online Import
echo ========================================
echo.

REM Check if SQL file is provided
if "%~1"=="" (
    echo ERROR: Please provide SQL file path
    echo Usage: fix_sql_dump.bat your_database.sql
    echo.
    pause
    exit /b 1
)

set INPUT_FILE=%~1
set OUTPUT_FILE=%~n1_fixed%~x1

if not exist "%INPUT_FILE%" (
    echo ERROR: File not found: %INPUT_FILE%
    echo.
    pause
    exit /b 1
)

echo Input file:  %INPUT_FILE%
echo Output file: %OUTPUT_FILE%
echo.
echo Processing...

REM Use PowerShell to fix the SQL file
powershell -Command ^
    "$content = Get-Content '%INPUT_FILE%' -Raw; ^
    $content = $content -replace 'ALTER TABLE groups ', 'ALTER TABLE `groups` '; ^
    $content = $content -replace 'CREATE TABLE groups ', 'CREATE TABLE `groups` '; ^
    $content = $content -replace 'INSERT INTO groups ', 'INSERT INTO `groups` '; ^
    $content = $content -replace 'DROP TABLE groups', 'DROP TABLE `groups`'; ^
    $content = $content -replace 'REFERENCES groups', 'REFERENCES `groups`'; ^
    $content = $content -replace 'groups_branch_id_foreign', '`groups_branch_id_foreign`'; ^
    $content = $content -replace 'FROM groups ', 'FROM `groups` '; ^
    $content = $content -replace 'UPDATE groups ', 'UPDATE `groups` '; ^
    $content = $content -replace 'TABLE IF EXISTS groups', 'TABLE IF EXISTS `groups`'; ^
    Set-Content '%OUTPUT_FILE%' -Value $content -NoNewline"

if errorlevel 1 (
    echo.
    echo ERROR: Failed to process file
    echo.
    pause
    exit /b 1
)

echo.
echo ========================================
echo   SUCCESS! Fixed SQL file created
echo ========================================
echo.
echo Fixed file: %OUTPUT_FILE%
echo.
echo You can now import this file to your online database.
echo.
pause
