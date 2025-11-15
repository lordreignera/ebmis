<?php

$ch = curl_init();

$payload = [
            'tx_ref' => "MI".time(),
            'amount' => '1000',
            'currency' => 'UGX',
            'redirect_url' => 'https://webhook.site/9d0b00ba-9a69-44fa-a43d-a82c33c36fdc',
            'payment_plan' => '112486',
            'customer' => [
                'email' => 'user@gmail.com',
                'phonenumber' => '0789497829',
                'name' => 'User name'
            ]
        ];

        $token = "FLWSECK_TEST-ac15ca78d30c9e67cbeb5a069601a805-X";

$message = json_encode($payload);
$message = preg_replace('/\s+/', '', $message);

//echo $message;

curl_setopt($ch, CURLOPT_URL, 'https://api.flutterwave.com/v3/payments');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch,CURLOPT_POSTFIELDS, $message);

$headers = array();
$headers[] = 'Content-Type: application/json';
$headers[] = 'Authorization: Bearer '.$token;
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);

?>

