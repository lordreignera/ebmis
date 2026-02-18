<?php
echo "Converting emuria.key from PKCS#8 to PKCS#1 format...\n\n";

// Read the PKCS#8 key
$pkcs8_key = file_get_contents(__DIR__ . '/certs/2026/emuria.key');

// Load the key
$key_resource = openssl_pkey_get_private($pkcs8_key);

if ($key_resource === false) {
    echo "❌ Failed to load PKCS#8 key\n";
    echo "OpenSSL errors:\n";
    while ($msg = openssl_error_string()) {
        echo "  - $msg\n";
    }
    die();
}

echo "✅ Successfully loaded emuria.key (PKCS#8)\n";

// Get key details to extract RSA parameters
$key_details = openssl_pkey_get_details($key_resource);

if ($key_details['type'] !== OPENSSL_KEYTYPE_RSA) {
    echo "❌ Key is not an RSA key\n";
    die();
}

// Extract RSA key components
$rsa = $key_details['rsa'];

// Function to convert number to ASN.1 DER integer
function asn1_int($num) {
    $hex = gmp_strval(gmp_init($num, 10), 16);
    if (strlen($hex) % 2) $hex = '0' . $hex;
    $bytes = hex2bin($hex);
    
    // Add padding byte if high bit is set (negative number in two's complement)
    if (ord($bytes[0]) & 0x80) {
        $bytes = "\x00" . $bytes;
    }
    
    $len = strlen($bytes);
    if ($len < 128) {
        return "\x02" . chr($len) . $bytes;
    } else {
        $lenBytes = '';
        $tempLen = $len;
        while ($tempLen > 0) {
            $lenBytes = chr($tempLen & 0xFF) . $lenBytes;
            $tempLen >>= 8;
        }
        return "\x02" . chr(0x80 | strlen($lenBytes)) . $lenBytes . $bytes;
    }
}

// Build PKCS#1 RSA private key structure (ASN.1 DER)
// RSAPrivateKey ::= SEQUENCE {
//   version           Version,
//   modulus           INTEGER,  -- n
//   publicExponent    INTEGER,  -- e
//   privateExponent   INTEGER,  -- d
//   prime1            INTEGER,  -- p
//   prime2            INTEGER,  -- q
//   exponent1         INTEGER,  -- d mod (p-1)
//   exponent2         INTEGER,  -- d mod (q-1)
//   coefficient       INTEGER,  -- (inverse of q) mod p
// }

$version = asn1_int('0');
$modulus = asn1_int(gmp_strval(gmp_init(bin2hex($rsa['n']), 16), 10));
$publicExponent = asn1_int(gmp_strval(gmp_init(bin2hex($rsa['e']), 16), 10));
$privateExponent = asn1_int(gmp_strval(gmp_init(bin2hex($rsa['d']), 16), 10));
$prime1 = asn1_int(gmp_strval(gmp_init(bin2hex($rsa['p']), 16), 10));
$prime2 = asn1_int(gmp_strval(gmp_init(bin2hex($rsa['q']), 16), 10));
$exponent1 = asn1_int(gmp_strval(gmp_init(bin2hex($rsa['dmp1']), 16), 10));
$exponent2 = asn1_int(gmp_strval(gmp_init(bin2hex($rsa['dmq1']), 16), 10));
$coefficient = asn1_int(gmp_strval(gmp_init(bin2hex($rsa['iqmp']), 16), 10));

$sequence = $version . $modulus . $publicExponent . $privateExponent . 
            $prime1 . $prime2 . $exponent1 . $exponent2 . $coefficient;

$len = strlen($sequence);
if ($len < 128) {
    $der = "\x30" . chr($len) . $sequence;
} else {
    $lenBytes = '';
    $tempLen = $len;
    while ($tempLen > 0) {
        $lenBytes = chr($tempLen & 0xFF) . $lenBytes;
        $tempLen >>= 8;
    }
    $der = "\x30" . chr(0x80 | strlen($lenBytes)) . $lenBytes . $sequence;
}

// Convert to PEM format
$pkcs1_key = "-----BEGIN RSA PRIVATE KEY-----\n";
$pkcs1_key .= chunk_split(base64_encode($der), 64, "\n");
$pkcs1_key .= "-----END RSA PRIVATE KEY-----\n";

echo "✅ Successfully converted to PKCS#1\n\n";
echo "PKCS#8 header: " . substr($pkcs8_key, 0, 30) . "...\n";
echo "PKCS#1 header: " . substr($pkcs1_key, 0, 30) . "...\n\n";

// Save to file
file_put_contents(__DIR__ . '/certs/2026/emuria_pkcs1.pem', $pkcs1_key);
echo "✅ Converted key saved to: certs/2026/emuria_pkcs1.pem\n\n";

echo "===== PKCS#1 KEY (for use in Prod files) =====\n";
echo $pkcs1_key;
echo "=============================================\n";
