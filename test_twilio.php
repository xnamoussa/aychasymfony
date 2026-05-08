<?php

require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$twilioSid = $_ENV['TWILIO_ACCOUNT_SID'] ?? '';
$twilioToken = $_ENV['TWILIO_AUTH_TOKEN'] ?? '';
$twilioFrom = $_ENV['TWILIO_FROM_NUMBER'] ?? '';
$to = '+21629051913';
$message = 'Test from simple CURL';

echo "SID: $twilioSid\n";
echo "From: $twilioFrom\n";

$endpoint = "https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Messages.json";

$ch = curl_init();

$postData = http_build_query([
    'To' => $to,
    'From' => $twilioFrom,
    'Body' => $message
]);

curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_USERPWD, "$twilioSid:$twilioToken");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$error = curl_error($ch);

if ($error) {
    echo "CURL Error: $error\n";
} else {
    echo "Response: $response\n";
}

curl_close($ch);
