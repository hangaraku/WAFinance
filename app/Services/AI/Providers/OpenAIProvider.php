<?php

namespace App\Services\AI\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenAIProvider implements AIProviderInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl = 'https://api.openai.com/v1';

    public function __construct(string $apiKey, string $model = 'gpt-4o')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function callChat(array $messages, array $functions = [], $functionCall = 'auto', ?string $systemInstruction = null): array
    {
        $url = "{$this->baseUrl}/chat/completions";

        // Format messages for OpenAI
        $formattedMessages = $this->formatMessages($messages, $systemInstruction);

        $payload = [
            'model' => $this->model,
            'messages' => $formattedMessages,
            'max_completion_tokens' => 4096,
        ];
        
        // gpt-5-nano only supports temperature=1 (default), other models support 0.7
        if (!in_array($this->model, ['gpt-5-nano'])) {
            $payload['temperature'] = 0.7;
        }

        // Add tools if functions provided
        if (!empty($functions)) {
            $payload['tools'] = $this->formatTools($functions);
            $payload['tool_choice'] = $this->formatToolChoice($functionCall);
            $payload['parallel_tool_calls'] = true; // Enable parallel calling
        }

        // Prompt caching works automatically for gpt-4o and newer models (gpt-5-nano, gpt-4o, etc.)
        // No explicit parameters needed - caching happens automatically for prompts >1024 tokens
        // In-memory cache: 5-10 min retention, auto-enabled for all models
        // Extended cache (24h): Only for gpt-5.1, gpt-5, gpt-4.1 via prompt_cache_retention='24h'
        
        // Optional: Add cache key for better routing when many requests share common prefixes
        // This helps maintain cache hit rates by routing similar requests to the same server
        if (in_array($this->model, ['gpt-5-nano', 'gpt-5', 'gpt-5.1', 'gpt-4o', 'gpt-4.1'])) {
            $payload['prompt_cache_key'] = 'wafinance_system_' . md5($systemInstruction ?? '');
        }

        Log::info('OpenAI Request Payload:', ['payload' => json_encode($payload)]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json'
        ])->timeout(60)->post($url, $payload);

        if ($response->failed()) {
            Log::error('OpenAI API Error:', ['body' => $response->body(), 'status' => $response->status()]);
            throw new Exception('OpenAI API request failed: ' . $response->body());
        }

        $data = $response->json();
        
        // Log cache usage metrics if available
        if (isset($data['usage'])) {
            $usage = $data['usage'];
            $cacheReadTokens = $usage['prompt_tokens_details']['cached_tokens'] ?? 0;
            $cacheCreationTokens = $usage['cache_creation_input_tokens'] ?? 0;
            $promptTokens = $usage['prompt_tokens'] ?? 0;
            
            $cacheHitRate = $promptTokens > 0 ? round(($cacheReadTokens / $promptTokens) * 100, 2) : 0;
            
            Log::info('OpenAI Response with Cache Metrics:', [
                'model' => $this->model,
                'prompt_tokens' => $promptTokens,
                'cached_tokens' => $cacheReadTokens,
                'cache_creation_tokens' => $cacheCreationTokens,
                'completion_tokens' => $usage['completion_tokens'] ?? 0,
                'cache_hit_rate' => "{$cacheHitRate}%",
                'cost_savings' => $cacheReadTokens > 0 ? '50% on cached tokens' : 'none'
            ]);
        } else {
            Log::info('OpenAI Response:', ['data' => json_encode($data)]);
        }

        return $this->parseResponse($data);
    }

    /**
     * Format messages from internal format to OpenAI format
     */
    protected function formatMessages(array $messages, ?string $systemInstruction): array
    {
        $formatted = [];

        // Add system prompt first
        if ($systemInstruction) {
            $formatted[] = [
                'role' => 'system',
                'content' => $systemInstruction
            ];
        }

        foreach ($messages as $msg) {
            $role = $msg['role'];

            // Convert internal roles to OpenAI roles
            if ($role === 'model') {
                $role = 'assistant';
            }

            // Handle regular content messages (user or assistant text)
            if (isset($msg['content']) && !empty($msg['content']) && $role !== 'function') {
                // Check if this is an assistant message with function calls already added
                if ($role === 'assistant' && (isset($msg['function_call']) || isset($msg['function_calls']))) {
                    // Don't add text content for messages that have function calls
                    // They will be handled below
                } else {
                    $formatted[] = [
                        'role' => $role,
                        'content' => $msg['content']
                    ];
                    continue;
                }
            }

            // Handle single function call (model output)
            if (isset($msg['function_call']) && $role !== 'function') {
                $toolCallId = $msg['function_call']['id'] ?? ('call_' . uniqid());
                
                $formatted[] = [
                    'role' => 'assistant',
                    'content' => null,
                    'tool_calls' => [
                        [
                            'id' => $toolCallId,
                            'type' => 'function',
                            'function' => [
                                'name' => $msg['function_call']['name'],
                                'arguments' => $msg['function_call']['arguments']
                            ]
                        ]
                    ]
                ];
            }

            // Handle multiple function calls (parallel)
            if (isset($msg['function_calls'])) {
                $toolCalls = [];
                foreach ($msg['function_calls'] as $funcCall) {
                    $toolCallId = $funcCall['id'] ?? ('call_' . uniqid());
                    $toolCalls[] = [
                        'id' => $toolCallId,
                        'type' => 'function',
                        'function' => [
                            'name' => $funcCall['name'],
                            'arguments' => $funcCall['arguments']
                        ]
                    ];
                }
                $formatted[] = [
                    'role' => 'assistant',
                    'content' => null,
                    'tool_calls' => $toolCalls
                ];
            }

            // Handle single function response
            if ($role === 'function' && isset($msg['name']) && isset($msg['content'])) {
                $toolCallId = $msg['tool_call_id'] ?? ('call_' . uniqid());
                $formatted[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCallId,
                    'content' => $msg['content']
                ];
            }

            // Handle multiple function responses (parallel)
            if (isset($msg['function_responses'])) {
                foreach ($msg['function_responses'] as $funcResp) {
                    $toolCallId = $funcResp['tool_call_id'] ?? ('call_' . uniqid());
                    $formatted[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCallId,
                        'content' => $funcResp['content']
                    ];
                }
            }
        }

        return $formatted;
    }

    /**
     * Convert internal function definitions to OpenAI tools format
     */
    protected function formatTools(array $functions): array
    {
        $tools = [];
        foreach ($functions as $func) {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $func['name'],
                    'description' => $func['description'],
                    'parameters' => $func['parameters']
                ]
            ];
        }
        return $tools;
    }

    /**
     * Convert internal function call config to OpenAI tool_choice format
     */
    protected function formatToolChoice($functionCall)
    {
        if ($functionCall === 'none') {
            return 'none';
        } elseif ($functionCall === 'auto') {
            return 'auto';
        } elseif (is_array($functionCall) && isset($functionCall['name'])) {
            return [
                'type' => 'function',
                'function' => ['name' => $functionCall['name']]
            ];
        }
        return 'auto';
    }

    /**
     * Parse OpenAI response into internal format
     */
    protected function parseResponse(array $data): array
    {
        $choice = $data['choices'][0] ?? null;
        if (!$choice) {
            return [
                'content' => 'Sorry, I encountered an error processing the response.',
                'function_call' => null,
                'function_calls' => null
            ];
        }

        $message = $choice['message'];
        $finishReason = $choice['finish_reason'];

        // Check for tool calls
        if (isset($message['tool_calls']) && !empty($message['tool_calls'])) {
            $toolCalls = [];

            foreach ($message['tool_calls'] as $toolCall) {
                $toolCalls[] = [
                    'name' => $toolCall['function']['name'],
                    'arguments' => $toolCall['function']['arguments'],
                    'id' => $toolCall['id'] // Store ID for tracking
                ];
            }

            // Multiple tool calls (parallel)
            if (count($toolCalls) > 1) {
                Log::info("Detected parallel function calls", ['count' => count($toolCalls)]);
                return [
                    'content' => null,
                    'function_call' => null,
                    'function_calls' => $toolCalls
                ];
            }

            // Single tool call
            return [
                'content' => null,
                'function_call' => $toolCalls[0],
                'function_calls' => null
            ];
        }

        // Regular text response
        return [
            'content' => $message['content'] ?? '',
            'function_call' => null,
            'function_calls' => null
        ];
    }
}
