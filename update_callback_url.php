<?php

/**
 * Quick script to update callback URL in .env
 * Usage: php update_callback_url.php https://abc123.ngrok.io
 */

if ($argc < 2) {
    echo "Usage: php update_callback_url.php <ngrok_url>\n";
    echo "Example: php update_callback_url.php https://abc123.ngrok.io\n";
    exit(1);
}

$ngrokUrl = rtrim($argv[1], '/');
$callbackPath = '/admin/loan-management/mobile-money/callback';
$fullCallbackUrl = $ngrokUrl . $callbackPath;

// Validate URL
if (!filter_var($ngrokUrl, FILTER_VALIDATE_URL)) {
    echo "[ERROR] Invalid URL format: {$ngrokUrl}\n";
    echo "Expected format: https://abc123.ngrok.io\n";
    exit(1);
}

// Check if URL is HTTPS
if (!str_starts_with($ngrokUrl, 'https://')) {
    echo "[WARNING] URL should use HTTPS, not HTTP\n";
    echo "FlexiPay/Stanbic might reject HTTP callbacks\n";
}

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    echo "[ERROR] .env file not found: {$envFile}\n";
    exit(1);
}

// Read .env file
$envContent = file_get_contents($envFile);

// Update or add FLEXIPAY_CALLBACK_URL
$pattern = '/FLEXIPAY_CALLBACK_URL=.*/';
$replacement = 'FLEXIPAY_CALLBACK_URL="' . $fullCallbackUrl . '"';

if (preg_match($pattern, $envContent)) {
    // Update existing
    $newContent = preg_replace($pattern, $replacement, $envContent);
    echo "[INFO] Updating existing FLEXIPAY_CALLBACK_URL\n";
} else {
    // Add new
    $newContent = $envContent . "\n" . $replacement . "\n";
    echo "[INFO] Adding new FLEXIPAY_CALLBACK_URL\n";
}

// Write back to .env
file_put_contents($envFile, $newContent);

echo "\n=== CALLBACK URL UPDATED ===\n";
echo "Old: http://localhost:84{$callbackPath}\n";
echo "New: {$fullCallbackUrl}\n\n";

echo "NEXT STEPS:\n";
echo "1. Clear config cache:\n";
echo "   php artisan config:clear\n\n";
echo "2. Verify configuration:\n";
echo "   php test_callback_system.php\n\n";
echo "3. Configure in Stanbic/FlexiPay dashboard:\n";
echo "   Webhook URL: {$fullCallbackUrl}\n\n";
echo "4. Test with mobile money payment\n\n";
echo "5. Monitor callback in ngrok web interface:\n";
echo "   http://127.0.0.1:4040\n\n";

echo "=== DONE ===\n";
