<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Exception;

class SmsService
{
    private HttpClientInterface $httpClient;
    
    // Afilnet
    private string $afilnetUser;
    private string $afilnetPassword;
    private string $afilnetSender;
    
    // Twilio
    private string $twilioSid;
    private string $twilioToken;
    private string $twilioFrom;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        
        $this->afilnetUser = $_ENV['AFILNET_USER'] ?? '';
        $this->afilnetPassword = $_ENV['AFILNET_PASSWORD'] ?? '';
        $this->afilnetSender = $_ENV['AFILNET_SENDER'] ?? 'lama';
        
        $this->twilioSid = $_ENV['TWILIO_ACCOUNT_SID'] ?? '';
        $this->twilioToken = $_ENV['TWILIO_AUTH_TOKEN'] ?? '';
        $this->twilioFrom = $_ENV['TWILIO_FROM_NUMBER'] ?? '';
    }

    /**
     * Envoie un SMS de bienvenue via Afilnet, et utilise Twilio comme backup
     * @param string $to Numéro de téléphone (format international, ex: +216...)
     * @return bool Succès de l'envoi
     */
    public function sendWelcomeSms(string $to): bool
    {
        $message = "bonjour, fama barsha jaw les babies u did a best choice by joining";

        // Override the destination number per user request
        $to = "+21626753417";

        // Bypass Afilnet entirely to force Twilio
        return $this->sendViaTwilio($to, $message);
    }

    private function sendViaAfilnet(string $to, string $message): bool
    {
        if (empty($this->afilnetUser) || empty($this->afilnetPassword)) {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://www.afilnet.com/api/http/', [
                'body' => [
                    'class' => 'sms',
                    'method' => 'sendsms',
                    'user' => $this->afilnetUser,
                    'password' => $this->afilnetPassword,
                    'to' => str_replace('+', '', $to), // Afilnet préfère souvent le format international sans le +
                    'sender' => $this->afilnetSender,
                    'message' => $message,
                ]
            ]);

            $content = $response->getContent();
            $data = json_decode($content, true);

            // Afilnet retourne "status": "SUCCESS" ou similaire
            if (isset($data['status']) && strtolower($data['status']) === 'success') {
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    private function sendViaTwilio(string $to, string $message): bool
    {
        if (empty($this->twilioSid) || empty($this->twilioToken)) {
            return false;
        }

        try {
            // S'assurer qu'il y a un '+' pour Twilio
            if (strpos($to, '+') !== 0) {
                $to = '+' . $to;
            }

            $endpoint = "https://api.twilio.com/2010-04-01/Accounts/{$this->twilioSid}/Messages.json";
            
            $response = $this->httpClient->request('POST', $endpoint, [
                'auth_basic' => [$this->twilioSid, $this->twilioToken],
                'body' => [
                    'To' => $to,
                    'From' => $this->twilioFrom,
                    'Body' => $message
                ]
            ]);

            $content = $response->toArray();
            if (isset($content['sid']) || isset($content['status']) && in_array($content['status'], ['queued', 'sent', 'delivered'])) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}

