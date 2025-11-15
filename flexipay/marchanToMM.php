<?php 
$privkeyfile = "-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC/hknoNc3ED78E
RMlb0lkBD7/IWEukdE9GwowIieX6XZrOt0GX0HNsx9oCrI3Li9q5GogUwYgp0+xQ
BXUmVALdtEg/qwW2MOIiQrM+frkkmud23X/6l+rnCXTv/DQm/f6t0YZE1cQtb8SW
EVko0tEcbJLDwcY7g/tw5hJBoMMXnQu7c/Uz95i8KOyLz5RMVJ6T7Fjgh70R/VSa
F7cuRI/iVWJNh5PnttOgDb4JoDE6iDfzNYZirmJ5hEVoIINcppEadqs0wEW9hc6W
kIV22SQ78qgNgpUbyc4pyfxNZqG7RCwGeJc1VSILZ55ChTHcgKpXR6mQ07s+rFAf
howuzGdLAgMBAAECggEANGxa/XsAq/pNZCs53G7Kmu5HJdz5M3X8nxcwQkQQlYOa
lJt3kkjl1zAq3dGCbGUHBOScu+WvUhemVs7vnoKfWDT5E6hJw3FE7HDKZEBGiBz4
X0JIvfxoOT4O6oNjeQrL86LmuB+092Tg79ymxXRS9Y9Iatm222KIaAIpnBoXXHZQ
LMFYHf4aBTqKuodLW6zRJZLeOnL8Q1zsFdotOH0gJTbYgt2uqWfCetrMQ/xER6md
6OMHfDxjKZ5cVTkyVDNR05rmJkh6c5duHBIOSwgKIYu02O8XeCUICS0mpm7680wv
cLfSJze9hugbaQqI5V8nd9PKg/d1vfBIUJ3bYlMc5QKBgQDqk0RliNsIEGAWmBkq
fy6aCIDybs73zyJPujGtaE9froz6E+Pd8+bOkTtEB0FDyq2BUh+E2feUGrGFdLqY
Yfvx9idtSavASJ3yWHRhKeu8+kbHNy3mD4qJDw8t00yCvUnSpTNbv9fJUbP6iFA3
ByY1cJ7AepVSoee6vHiNliII3QKBgQDRBG34U7Sxj5kzJ593mD5UtqHBCCnUUbdT
+1v+cmdC72r29A7+Ec3d0XXKwBw09PuQbP9sioy6xrkIwMOmIVpeT1GttuzPGAvO
WYz4Wg1KHwCbSvCaTwkLdgEWIzJwwserY7ioZVuZb2BkdHGs2dkV2XLExUyTpeEr
/XMwpjOaRwKBgFc0oHzOv/7jd5Vuvgxac8y31JhMMY1W5/6TzdwVp0x+69Icit38
ypWI0Gud9tlpA8/L5APTtILO2agvmR8FblCpnka22K8HUBDEaZ+logoDUUTGcr3Q
kUQa4R28K/l+vW8eE1XMoEArq6k7+/Y5Ji8/ywTrjY/GuQtm/bpFUinRAoGBAMgu
1OAD00hHvrNWnI0fG3to2uyUU/OMO+fMEmRUz3807B4Oyxkcli1/AbCoY5t4kkLV
kaAz5eqwjuDKNdezk+hFUXXtf0osvonoDHKDVL0LijoxANTZI1F9uDaqiRGkCzWj
sWRehuch25D6UTD6B8a8VwYL7HZwZYMLH7qVQ1DbAoGBAJPkxtp51CaLjM+/OdjD
/bMtbIpYpJ1/OsqM7v4gy6JMw/dhjJyD3tzM/MGKdW4rZrflK2BEnMC3qurKkokx
TLsDvrFxDoxHLwt05aCemH+pMVJpCFxdnYsnIVOoOE2zBOYuyb/CRpLQ5B7EHhM2
Tmg+SmnOKkH/kjnJoPhV3PKf
-----END PRIVATE KEY-----";
$payload = '{"msisdn":"256759889380","requestId":"202211141638","merchantCode":"229977","clientId":"EBIMSUAT"}';
# remove white spaces from payload
$message = json_encode(json_decode($payload), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
# Hash the JSON string using SHA256
$hash_obj = hash('sha256', $message); 

openssl_private_encrypt($hash_obj, $signature, $privkeyfile, OPENSSL_PKCS1_PADDING);
# Encode the signature in base64
$encoded_signature = base64_encode($signature);

var_dump($encoded_signature);

$ch = curl_init();
$key = "b8b33bfd8de0e95b00dd574472be9bbf";
$secret = "5e433663f3d699c70c91592a8bb6c43e";
$token = "AAIgYjhiMzNiZmQ4ZGUwZTk1YjAwZGQ1NzQ0NzJiZTliYmZkIVUei3IZTlpJ1fiRMN27_CkjsXLEX48Zkbf5Rl3vx1jWZL-wr8GmQfpejfybvFX7PqlQazdgS7U9Km_xJ4-5p54z5kWlltC1J5fMbdQpJIVcGi_45-SCnKo8N0BMYjM";//jwt


curl_setopt($ch, CURLOPT_URL, 'https://gateway.apps.sandbox.stanbicbank.co.ug/fp/v1.1/validatemerchant');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, "C:\\xampp\\htdocs\\flexipay\\certs\\reals\\cert.pem");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, "C:\\xampp\\htdocs\\flexipay\\certs\\reals\\cert.pem");
curl_setopt($ch,CURLOPT_POSTFIELDS, $message);


echo $message;


$headers = array();
$headers[] = 'Content-Type: application/json';
$headers[] = 'Authorization: Bearer '.$token;
$headers[] = 'password: '.$secret;
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
