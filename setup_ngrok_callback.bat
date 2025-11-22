@echo off
echo ============================================
echo  SETUP NGROK FOR LOCAL CALLBACK TESTING
echo ============================================
echo.

REM Check if ngrok is installed
where ngrok >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] ngrok is not installed!
    echo.
    echo Please install ngrok:
    echo 1. Download from: https://ngrok.com/download
    echo 2. Extract ngrok.exe to a folder
    echo 3. Add that folder to your PATH, or place ngrok.exe in C:\Windows\System32
    echo.
    echo Alternative: Run ngrok directly from its folder:
    echo    cd path\to\ngrok
    echo    ngrok http 84
    echo.
    pause
    exit /b 1
)

echo [OK] ngrok is installed!
echo.
echo Starting ngrok tunnel for localhost:84...
echo.
echo INSTRUCTIONS:
echo 1. Keep this window open while testing
echo 2. Copy the HTTPS URL shown below (e.g., https://abc123.ngrok.io)
echo 3. Update .env: FLEXIPAY_CALLBACK_URL="https://abc123.ngrok.io/admin/loan-management/mobile-money/callback"
echo 4. Run: php artisan config:clear
echo 5. Configure the same URL in Stanbic/FlexiPay dashboard
echo 6. Test mobile money payment - callbacks will work!
echo.
echo ============================================
echo.

ngrok http 84
