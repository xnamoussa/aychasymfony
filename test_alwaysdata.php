<?php
$ch = curl_init('https://api.alwaysdata.com/v1/account/');
curl_setopt($ch, CURLOPT_USERPWD, 'emna.awini.work@gmail.com:emmaawini123');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP $httpcode\n";
echo $response;
