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

//converted private key from https://8gwifi.org/pemconvert.jsp by PKCS#8/PKCS#1 RSA,DSA,EC Converter
$privkeyfile = "-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAuXMmLN48OjsVn480oigd8XNx47JOyMX4lFdlF/260QyHinBn
ZyoZsFxyKlvyACPn9a7eCYc3Hk1CdZ6XaRX2NF6dK4KLdklD1wxWgIVgEeZlg3cs
QuphYbxVVFvf4N6AVzLHBooY6ltQk8CURBHy1GgD5wGfE9gSHAtaZ4ZIbuIxYwx+
dfVIVYjl4TQvOBEn8QgJaA6Fq7bUmhwIdGSXdssgjEnAHaVZXsZLTBezidS09B8X
YDr6kMf7lItACD/qtwPsDlngdpIOCEMKp9XyRUj0T9snGBKr5h3Eat6HUN/Q7uA9
RujNnFoW+J5iHJBRxzRL8wS4gq35keI7MdfJnwIDAQABAoIBAAgRRVDgXhheZ6No
VG7VdfACCKtSH3FGg3jYkHJJvG3JsL/KAgWP5EwyyVikZVOyPC4I7GnXswMjc0ew
nX+Zz/sZPpcc97oul7/sLnsq0jIVJsdgUNGcZp4c1k10LboXk9e3Qsc7DLhtPoUe
9JQ9f6XT3I+ZF3WCic8kg0tLoS0JoQeWVL0xY901EwUZTc6JZedSg0M2DTDjf+zu
DxwlM4QQg4RHzgs1On8dW0WcvFg66aI/zak707f/fuBilFpOW4Um2b2HWMF0rziu
62v/KP3ATsaOdYVaBwU4BD1sjMw8NUz4iikVWxIqSbI4Krtsnx3NqURTxlFH6RwS
TjFrpkkCgYEA5K+/kS4GHt/7051glxsTfGNyV9xwq4x/shfltu17rINwRAFvTqpR
AFobIi9XyPOsSoK5OtGVljYOU1ynPZ4mYYYKeT90Qyf4Czg1FrWfpTgxJygkURbk
R2n/Mla4o30tfTnS6HZkENCCEbzmvqfSV58tvbS2UIts4rfzh7jPq10CgYEAz5lo
X89Un3vAl5eTCp0bcR92CKtgCc6y4uWGUgwigWt+0PpB8ghziE7wziedK5mMAi2+
lcwFS5ZUHMfJuT5Oh4eAashVj5YgjMqnfVZHc9mGYQ52FpUzYzSkDTw27kMhFV/x
TdgxwT04YX63GqXqvEAzLS2Z3TsHDEHgIX5u9SsCgYBKVrc3QnbK4pTCHY6gkDSt
YsZwuUAHBA0en5YU+O4TDkcYVD8Sm1rpemEHo8wtjsibEBOWgzrVMY1Gm//hj996
JFCTSYVJr1x6iTL4xuG2m6WezPXBRme+rz495uLugmqfIoTk/Fda/+zIR1fa8kL2
KNB9spjxZeFncdTAcdtQIQKBgEcMLIFltoNiWfZHhKZEQGkFqGKtLBAPMn+ep8qa
ppB+Vod0rm8D325N/fG/8vVB9n2kZC5mBYXp691xrqL8JOoTQKrK5yVd7sPgc1Pc
3FVUo73Bsj5mT5DrKh3xdqcySDdFf5Lxo42Lwyjysf2nvN8yZZFXouno0q+qN+ee
mqDDAoGBALeo28e+JpBOkjff7lM0qLXklwWSAcBJAaxTZhfrxg4kJ2I5momqbeER
SisK67y0o4BRr1IM0IuyZ9imIdBHd/zrB924umDXX+Wsi8MboKfi+RDkqSX1DgrG
+JHJdeSmauu96uUVstJQuoJtgTnZZLcu3MOhxRqVgHRBvDJk2BrU
-----END RSA PRIVATE KEY-----
";

$private_key = $privkeyfile;

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