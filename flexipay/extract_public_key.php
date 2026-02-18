<?php
echo "===== EXTRACTING PUBLIC KEY FOR STANBIC BANK =====\n\n";

// Load certificate
$cert = file_get_contents(__DIR__ . '/certs/2026/emuria.crt');
$x509 = openssl_x509_read($cert);

// Extract public key
$pubkey = openssl_pkey_get_public($x509);
$pubkey_details = openssl_pkey_get_details($pubkey);

// Export to PEM format
$public_key_pem = $pubkey_details['key'];

// Save to file
file_put_contents(__DIR__ . '/certs/2026/emuria_public.pem', $public_key_pem);

echo "✅ Public key extracted and saved to: certs/2026/emuria_public.pem\n\n";

echo "===== PUBLIC KEY (PEM FORMAT) =====\n";
echo $public_key_pem;
echo "\n===================================\n\n";

// Also extract in different formats that Stanbic might need
$modulus = base64_encode($pubkey_details['rsa']['n']);
$exponent = base64_encode($pubkey_details['rsa']['e']);

echo "===== RSA PUBLIC KEY COMPONENTS =====\n";
echo "Modulus (base64, first 80 chars): " . substr($modulus, 0, 80) . "...\n";
echo "Exponent (base64): " . $exponent . "\n\n";

echo "===== WHAT TO SEND TO STANBIC BANK =====\n\n";

echo "1. Certificate file: certs/2026/emuria.crt\n";
echo "2. Public key file: certs/2026/emuria_public.pem\n";
echo "3. Client ID: EBIMSPRD\n";
echo "4. Domain: emuria.net\n\n";

echo "Contact Details:\n";
echo "- Email your Stanbic Bank API support contact\n";
echo "- Subject: Update Certificate/Public Key for EBIMSPRD\n";
echo "- Attach: Both certificate and public key files\n";
echo "- Request: Please update our registered public key\n\n";

echo "=========================================\n";
