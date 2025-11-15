<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://gateway.apps.sandbox.stanbicbank.co.ug/ug/oauth2/token',
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
    'Authorization: Basic ZDliNzc3MzM1YmRlMmU2ZDI1ZGI0ZGQwNDEyZGU4NDY6M2VlNzllYzY4NDkzZWNjMGRjYTRkMDdhODdlYTcxZjAK'
  ),
));

$response = json_decode(curl_exec($curl),true);
//print_r($response);
print_r($response['access_token']);

curl_close($curl);
