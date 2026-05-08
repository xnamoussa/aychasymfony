<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TranslationService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function translate(string $text, string $from = 'fr', string $to = 'en'): string
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.mymemory.translated.net/get', [
                'query' => [
                    'q'        => $text,
                    'langpair' => $from . '|' . $to
                ]
            ]);

            $data       = $response->toArray();
            $translated = $data['responseData']['translatedText'] ?? $text;

            // Remove "MYMEMORY" disclaimer if present
            $translated = preg_replace('/\[.*?\]/', '', $translated);

            return trim($translated);
        } catch (\Exception $e) {
            return $text;
        }
    }
}
