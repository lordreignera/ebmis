<?php

$curl = curl_init();
$key = "d9b777335bde2e6d25db4dd0412de846";
$secret = "3ee79ec68493ecc0dca4d07a87ea71f0";
$token = base64_encode($key.":".$secret);

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://gateway.apps.platform.stanbicbank.co.ug/ug/oauth2/token',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => 'grant_type=client_credentials&scope=Create',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/x-www-form-urlencoded',
    'Authorization: Basic '.$token
  ),
));

$response = json_decode(curl_exec($curl),true);
//print_r($response);
 $gottoken = $response['access_token'];

curl_close($curl);

//Converted emuria.key from PKCS#8 to PKCS#1 format using convert_emuria_key.php (February 2026)
$privkeyfile = "-----BEGIN RSA PRIVATE KEY-----
MIIEogIBAAKCAQEAsEfqLhi3pC5mDWqy7iKCmYJihmroHqWPOts++ydJfts5ymtg
CO2Klae7AYJn9NEd0zuv8PbmR1IAb1iDzZzEaZRTl38GF/ypiP4L229CH3/qzQ6g
ywAY8rFD/nwKmnuweg3jiXiEFLZA5Z0DfFcV8RetvQAnx/ukBpz8nCn7gZrmK6ZT
1nVC3OCYFU5It7e9dxiqoyhNR5rx5mtGqUU24ZwAqAk5P23ge0jpO7TItMAqQKWh
DXoB7lA6XGhHG5h6FbDlKtXo0xImIa8EqsWSmTKkYMZw5k/oRlPfJ//Kg0RDTWu7
0Kt4zC/KC0zrtdOUFFv5V/NQW5JCpkQCTohvDQIDAQABAoIBAAoBiwFi1cmz+Ib6
b32k59Te2cjXeKWEsESe/Uw0Rq+0sesTfTgEg8FK7AqB5HS5CgBbevkqipexx+SK
GbEqHNwBV11aEHZ8GQN1qCakghRXpnRNSEM9lizwcvOXBuMN8k57S1caSCUE01o1
N2VvbAdrWKlJwPhRCFZ3wr76gxQwJoK152jYkaLZwN3cggZO8TSCx4ab0QORoYhh
3iVEo91ePjUqS6gt8qzC46yzTDXkEtdb2Uao/va13ryRvjksDYi5JxkJ7X84b43H
xQOU7UnrtNO9MzQlW8S3GAbj36Ziyyg5Wr+tvKqr2x7gk5dX5XZNTWVySFwPqqqS
CmMmn+ECgYEA5ESCrN00uMzB/WFn0y9LnbXk4YKSIPcUqJ4SVKu5pF5Se6SvI70U
yhB0ZLzmNfMmliHSGVKjD8C09hlbmX4+w4ofOY3w9FZq93EbrhpDddBBLTMzqk36
PH0tn7mfzixkWWkBGcrvbVC7Lv4Jvi97VYeDUDYe0Hmrb0efY2EcdScCgYEAxbKI
ziXY3g9V8+qVibV/+hScHCm3OzirXypiZ91sHRwxDMLng/81qB+S+TzQKYWSXwhY
KoQuGC953ArclteNAIoB/gruajkURe9iJeL0kHol2lAeInE1MflmVZdll297Dx8D
elAGEYKTlnWcMe9B5GLQstpIQ2/wRbpCSK+xIqsCgYAvOt2u1rYp5nPc8WKCF68V
mqUY4+NIXtcvbEVur3lhwQJgAtsaEe1TQcRTc6JOV1kMh0LpamfCwqSupuCFCdIC
s3lydyP76kWHnSeVBmoe3lAeAhIWkrvL+DqQad/e0OCSf19y7sJLZADW4EkzyK9E
Kx3IYupNSF9oTvFzpow00QKBgHG4Ct2aA69oXubZr37xOlZd+JZyoIWeSWWKeeSJ
B6GPD9/pVUcmTHUTBHX9tzfLL7EemaiLNACRfqVGUjEqeF8xA4hgPVg40SKRWoG0
lT1uJcv4ff0N5a2DaowddECxzbWa/2MiGPuFguPvbxOCLwLynF3lFeBEyY8yXuJ0
vY3VAoGAUKUwmU3YgnAkY81ni1B6UT7E5oovXlO6Tn0/9FVZGLxnFDvGhmE4cF7b
i86ERxseE7Jm05A7sGIsTnQ284w68CcaN6q1p0BMZRjs3CNMG4a6thE3yrJQOTnY
L/dWPmwzs1D/4A0KzGuLnwW3Aqc88dxuWmUVlBIYScu8jGFd6go=
-----END RSA PRIVATE KEY-----
";

$private_key = openssl_pkey_get_private($privkeyfile);

if ($private_key === false) {
    die(json_encode(['error' => 'Failed to load private key', 'details' => openssl_error_string()]));
}

$payload = [
    "requestId" => $_POST['reference'], //pass the transactionrefNumber
    "clientId" => "EBIMSPRD",
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
//echo $message."\n\n";
//echo $encoded_signature . "\n\n";

$ch = curl_init();
$password = "I4de39GwV739/lqXBXoXzJmZ1nKwvp0oIXZYa8UsPnJQoFlAKwHZNISdx6L3f/Ga";
$token = $gottoken;//jwt


curl_setopt($ch, CURLOPT_URL, 'https://gateway.apps.platform.stanbicbank.co.ug/fp/v1.1/merchantpaymentstatus');
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