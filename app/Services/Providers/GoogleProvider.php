<?php

namespace App\Services\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleProvider
{
    private $apiKey;
    private $baseUrl;
    private $model;

    public function __construct(string $apiKey, string $baseUrl, string $model)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->model = $model;
    }

    /**
     * Call the Google Generative Language API with a prepared payload
     */
    public function callGenerate(array $payload): array
    {
        $url = $this->baseUrl . '/models/' . $this->model . ':generateContent';

        $response = Http::withHeaders([
            'X-goog-api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->post($url, $payload);

        if (!$response->successful()) {
            Log::error('Google Provider generate error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
                'payload' => $payload,
            ]);
            throw new \Exception('Google Generative API Error: ' . $response->body());
        }

        return $response->json();
    }
}
