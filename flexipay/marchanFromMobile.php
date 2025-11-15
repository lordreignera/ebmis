<?php
//converted private key from https://8gwifi.org/pemconvert.jsp by PKCS#8/PKCS#1 RSA,DSA,EC Converter
$privkeyfile = "-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA13Vmh+VrdymOictkXYr9n5azkxv4KDPSm9yrFAVmrKBuYuGq
TvygDW4hLRTKO5GJs4hHSASUul7WNKCn3RxJ/oznHaRiX65szIvCvd42SHbUgxnc
4OrEzrAJRnHPJo2D6Zr+WsMTe11nx5oI/AfzKw7dMrDUMD9UdIMI7m+EobZc+PT/
Tnq7waMSNtbcO4k16B5r31uMPajaDiSQshCAyEIV2FDqHSs2yV6awsJvG2eOLOFN
9BgqIQhLBFU9U4WacqTiX5/LSKJ5V44DHP0irw7RRiGxZpX4ku+sFZ6T/edOwwPk
javtrI72geyR+AhraJU6cyZMgv/U/TphvoJChwIDAQABAoIBAQCWWembqX/tnsVF
6PX3xPcpd3uGi70HKOquMzX6+a3lhuqg/ALhra5u0Mw77kcVfIEQFGhRnEjBKU7n
WLjrNkN4a5EYAU9Yn5pyvpC9+CP/O1Uey1x0Y7/Ez9kZHHBG4fgMe0lFwt2Ed/Dk
u9vLLC0Hfg7jwbvAk2D3ET2ZTZ7LTPLu2R3OFWfBtoy1dbMqvuRH3304+Fj3J0Mg
6EJFGOlY0i0/eIER5xXEBScfNxkGZ0YKA68GbBHQtGI2F9D15CMLkt03+dgIcZxc
LPULx2RznCOjM7icBVLHAnRq5lTQAVtyz5C/DSsHjV9p9UqeemN+lYxewD5SI4sZ
KZLRpj55AoGBAP+P8swR2MDrcqwVq2cIi6yjAdeUQjMP3agJNwsbpLi11r8Z93Gr
SkeOl+BlJq57+JZ3M/FOdwr7FgooQNeKdXQgyFsFyvdcXVBbyF6Smc4UIvfaF77z
LgcLUinJ4x7jX/cx/KG1d/ycTyxu4Vgc3Daq4DCxEdO/G6GAtnX+b//NAoGBANfT
3lq3Y6lT6XClydMdczd40xYfwLWBMsSpVgCHLtvzbtwCmo6/2paq69Wbjpiydsj+
xJ1T8nR9/dXiguP1mn9WDfxSTsSsVFvlVauoIE1fdve/ncFwT5VYRi6s3uXsbjvZ
8D1+yoq9Km2+coRsrILhLfuCodGuwsUw13vM6u+jAoGBAJIdXTYr5f+3HiMhaJRK
IIGd2UnGbGsBYTvXuO7S6UTqQlOUpxMIWjm7Xz5e1tTf8Gsm0D3hHNLcZ+d6yEfz
09+HdsYD892lo3x0XYUk0GcwwCVxPi5gnypL4Lgfw4k/evi8TbKvLGDzhZjj9FcK
eSWQYQm103l7RHL8QlYIGUTpAoGAYXMZZ858Il1v/tvsl/UpK7fTX57wrUNrv95R
paVkJA3zVUWbsa6wrOz51RYKuamC9tgJwJvB0pV8wlEnFnSz0KDzaaVkSWsiH+gZ
2YrtIuJi3hRXz5q9ZEpaTgLiFeC+GSobTjjsjN5CxRCDtoU3E1VHJNPj6sBE0zJt
aUmqHo8CgYBgv6MtKRQBhT1QxbafApEAnAODJNUqCG3zPzV35B8nr3fCzGVgzA3+
9NmLShKs4LJnMsq/f01tvb788PSVYbY5ePNqLmleHW2SPX4d7lEnu8jPEYD50UsR
f1YKt6UQlSo/k8YfO9k/ElAvO/Thin2kvjySiuvOFHXfhQDlMVAU1Q==
-----END RSA PRIVATE KEY-----
";

$private_key = $privkeyfile;

$payload = [
    "msisdn" => "256772899768",
    "requestId" => "Ebims".time(),
    "merchantCode" => "229977",
    "clientId" => "EBIMSUAT",
    "referenceNumber" =>time(),
    "amount" =>"1000",
    "sourceSystem" =>"MTN",
    "narrative" =>"Test payment"
];

$message = json_encode($payload);
$message = preg_replace('/\s+/', '', $message);

$message_bytes = $message;

$signature = openssl_sign(
     $message,
     $encrypted_data,
    $private_key,
    OPENSSL_ALGO_SHA256
);

$encoded_signature = base64_encode($encrypted_data);
echo $message."\n\n";
echo $encoded_signature . "\n\n";
//

$ch = curl_init();
$key = "b8b33bfd8de0e95b00dd574472be9bbf";
$secret = "5e433663f3d699c70c91592a8bb6c43e";
$password = "oE2znOqoWdR+vF67GfjNira7mjEtGcCGBO8/sSv7okOy+Nw0T16TG0riOJN6tnqb";
$token = "AAIgYjhiMzNiZmQ4ZGUwZTk1YjAwZGQ1NzQ0NzJiZTliYmZ1hZ9OxjWKdvNMthoA_0rRPQxXXiJc87LvrOJyE9R9qFBhwCjjWwL1Y5aK5E-0d2DywCkzuKZ8XhFgAlIPnD4XbqB3taBpeQW_eMu_H2IN5_lAcqJ6b1zZW1bTY4qmQs8";//jwt


curl_setopt($ch, CURLOPT_URL, 'https://gateway.apps.sandbox.stanbicbank.co.ug/fp/v1.1/merchantpayment');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, "certs/reals/cert.pem");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, "certs/reals/cert.pem");
curl_setopt($ch,CURLOPT_POSTFIELDS, $message);

$headers = array();
$headers[] = 'Content-Type: application/json';
$headers[] = 'Authorization: Bearer '.$token;
$headers[] = 'password: '.$password;
$headers[] = 'x-signature: '.$encoded_signature;
$headers[] = 'X-IBM-Client-Secret: '.$secret;
$headers[] = 'X-IBM-Client-Id: '.$key;
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);


?>