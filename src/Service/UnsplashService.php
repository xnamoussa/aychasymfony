<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class UnsplashService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        // Unsplash API key — move to .env as UNSPLASH_API_KEY if needed
        $this->apiKey = 'YFUq9CbgjnPrphT4HZgoVAlwa1FaCifaaMHbLUzwrtY';
    }

    public function fetchImageUrl(string $query): ?string
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.unsplash.com/photos/random', [
                'query' => [
                    'query'       => $query,
                    'client_id'   => $this->apiKey,
                    'orientation' => 'landscape',
                    'w'           => 800,
                    'h'           => 400
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray();

            return $data['urls']['small'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
