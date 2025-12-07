<?php

namespace App\Services\AI\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiProvider implements AIProviderInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct(string $apiKey, string $model = 'gemini-2.0-flash-exp')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function callChat(array $messages, array $functions = [], $functionCall = 'auto', ?string $systemInstruction = null): array
    {
        $url = "{$this->baseUrl}{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => $this->formatMessages($messages),
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
            ]
        ];

        // Add system instruction if provided
        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]]
            ];
        }

        // Add tools if functions are provided
        if (!empty($functions)) {
            $payload['tools'] = [
                [
                    'function_declarations' => $functions
                ]
            ];

            // Configure tool usage
            if ($functionCall === 'none') {
                $payload['tool_config'] = ['function_calling_config' => ['mode' => 'NONE']];
            } elseif ($functionCall === 'auto') {
                $payload['tool_config'] = ['function_calling_config' => ['mode' => 'AUTO']];
            } elseif (is_array($functionCall) && isset($functionCall['name'])) {
                $payload['tool_config'] = [
                    'function_calling_config' => [
                        'mode' => 'ANY',
                        'allowed_function_names' => [$functionCall['name']]
                    ]
                ];
            }
        }

        Log::info('Gemini Request Payload:', ['payload' => json_encode($payload)]);

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error('Gemini API Error:', ['body' => $response->body()]);
            throw new Exception('Gemini API request failed: ' . $response->body());
        }

        $data = $response->json();
        Log::info('Gemini Response:', ['data' => json_encode($data)]);

        return $this->parseResponse($data);
    }

    protected function formatMessages(array $messages): array
    {
        $formatted = [];
        
        foreach ($messages as $msg) {
            $role = $msg['role'] === 'user' ? 'user' : 'model';
            $parts = [];

            if (isset($msg['content']) && !empty($msg['content'])) {
                $parts[] = ['text' => $msg['content']];
            }

            // Handle function calls in history (model output)
            if (isset($msg['function_call'])) {
                $args = json_decode($msg['function_call']['arguments'], true);
                // Ensure args is an object (associative array), not a list
                if (is_array($args) && empty($args)) {
                    $args = new \stdClass(); // Empty object
                }
                
                $parts[] = [
                    'functionCall' => [
                        'name' => $msg['function_call']['name'],
                        'args' => $args
                    ]
                ];
            }

            // Handle function responses in history (function role)
            if ($msg['role'] === 'function') {
                $role = 'function'; // Gemini uses 'function' role for responses
                $parts = [
                    [
                        'functionResponse' => [
                            'name' => $msg['name'],
                            'response' => ['content' => $msg['content']] 
                        ]
                    ]
                ];
            }

            $formatted[] = [
                'role' => $role,
                'parts' => $parts
            ];
        }

        return $formatted;
    }

    protected function parseResponse(array $data): array
    {
        if (!isset($data['candidates'][0]['content']['parts'])) {
            return [
                'content' => 'Sorry, I encountered an error processing the response.',
                'function_call' => null,
                'function_calls' => null
            ];
        }

        $parts = $data['candidates'][0]['content']['parts'];
        
        // Check for multiple function calls (parallel function calling)
        $functionCalls = [];
        $textContent = '';
        
        foreach ($parts as $part) {
            if (isset($part['functionCall'])) {
                $functionCalls[] = [
                    'name' => $part['functionCall']['name'],
                    'arguments' => json_encode($part['functionCall']['args'])
                ];
            } elseif (isset($part['text'])) {
                $textContent .= $part['text'];
            }
        }
        
        // If multiple function calls detected, return them
        if (count($functionCalls) > 1) {
            Log::info("Detected parallel function calls", ['count' => count($functionCalls)]);
            return [
                'content' => null,
                'function_call' => null,
                'function_calls' => $functionCalls
            ];
        }
        
        // Single function call (backward compatibility)
        if (count($functionCalls) === 1) {
            return [
                'content' => null,
                'function_call' => $functionCalls[0],
                'function_calls' => null
            ];
        }

        // Standard text response
        return [
            'content' => $textContent ?: '',
            'function_call' => null,
            'function_calls' => null
        ];
    }
}
