<?php

namespace App\Services;

use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MessagingService
{
    protected AIService $aiService;
    protected const HISTORY_LIMIT = 50; // Max messages in Redis
    protected const PROMPT_MESSAGE_LIMIT = 20; // Max messages sent to AI (to control costs)
    protected const REDIS_TTL = 86400; // 24 hours

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Validate webhook signature
     */
    public function validateWebhook(array $headers, string $payload): bool
    {
        $secret = config('services.messaging.webhook_secret');
        
        if (empty($secret)) {
            Log::warning('Messaging webhook secret not configured');
            return true; // Allow in development if not configured
        }

        // Support multiple signature formats
        $signature = $headers['x-webhook-signature'] ?? 
                    $headers['x-signature'] ?? 
                    $headers['signature'] ?? 
                    null;

        if (!$signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process incoming webhook message
     */
    public function processIncomingMessage(array $payload): array
    {
        try {
            // Extract data from payload (flexible schema for n8n/Evolution API)
            $from = $payload['from'] ?? $payload['sender'] ?? $payload['phone'] ?? null;
            $content = $payload['message'] ?? $payload['text'] ?? $payload['body'] ?? '';
            $externalId = $payload['id'] ?? $payload['message_id'] ?? $payload['external_id'] ?? null;
            $channel = $payload['channel'] ?? 'whatsapp';
            $to = $payload['to'] ?? $payload['recipient'] ?? null;

            if (empty($from) || empty($content)) {
                return [
                    'success' => false,
                    'error' => 'Missing required fields: from or message',
                ];
            }

            // Normalize phone number (remove special characters)
            $from = $this->normalizePhoneNumber($from);

            // Check if message already processed (idempotency)
            if ($externalId && Message::where('external_id', $externalId)->where('channel', $channel)->exists()) {
                Log::info("Message already processed: {$externalId}");
                return [
                    'success' => true,
                    'message' => 'Message already processed',
                    'duplicate' => true,
                ];
            }

            // Find user by WhatsApp number
            $user = User::where('whatsapp_number', $from)->first();

            if (!$user) {
                // Handle anonymous/unregistered users
                return $this->handleUnregisteredUser($from, $content, $externalId, $channel, $to);
            }

            // Get conversation history
            $history = $this->getConversationHistory($user->id, $from, $channel);

            // Build user context for AI
            $userContext = [
                'user_id' => $user->id,
                'name' => $user->name,
                'whatsapp_number' => $user->whatsapp_number,
                'channel' => $channel,
            ];

            // Call AI service with history
            $aiResponse = $this->aiService->processMessage($user, $content, [
                'timestamp' => now()->toISOString(),
                'channel' => $channel,
                'external_id' => $externalId,
                'user_context' => $userContext,
                'conversation_history' => $history,
            ]);

            $replyContent = $aiResponse['response'] ?? 'Maaf, saya tidak bisa memproses permintaan Anda saat ini.';

            // Persist incoming message
            $incomingMessage = $this->storeMessage([
                'user_id' => $user->id,
                'external_id' => $externalId,
                'channel' => $channel,
                'from' => $from,
                'to' => $to,
                'role' => 'user',
                'content' => $content,
                'metadata' => [
                    'raw_payload' => $payload,
                ],
            ]);

            // Persist AI response
            $outgoingMessage = $this->storeMessage([
                'user_id' => $user->id,
                'external_id' => null, // Will be set when sent
                'channel' => $channel,
                'from' => $to,
                'to' => $from,
                'role' => 'assistant',
                'content' => $replyContent,
                'metadata' => [
                    'ai_model' => $aiResponse['model'] ?? 'unknown',
                    'in_reply_to' => $externalId,
                ],
            ]);

            // Update Redis history
            $this->addToRedisHistory($user->id, $from, $channel, $incomingMessage, $outgoingMessage);

            return [
                'success' => true,
                'reply' => $replyContent,
                'to' => $from,
                'channel' => $channel,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('MessagingService error: ' . $e->getMessage(), [
                'payload' => $payload,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'Maaf, terjadi kesalahan. Silakan coba lagi nanti.',
            ];
        }
    }

    /**
     * Handle messages from unregistered users
     */
    protected function handleUnregisteredUser(string $from, string $content, ?string $externalId, string $channel, ?string $to): array
    {
        // Store message without user_id
        $this->storeMessage([
            'user_id' => null,
            'external_id' => $externalId,
            'channel' => $channel,
            'from' => $from,
            'to' => $to,
            'role' => 'user',
            'content' => $content,
            'metadata' => ['unregistered' => true],
        ]);

        $reply = "Maaf, nomor WhatsApp Anda belum terdaftar di sistem kami. Silakan daftar terlebih dahulu di aplikasi Cashfloo.";

        // Store reply
        $this->storeMessage([
            'user_id' => null,
            'external_id' => null,
            'channel' => $channel,
            'from' => $to,
            'to' => $from,
            'role' => 'system',
            'content' => $reply,
            'metadata' => ['unregistered_reply' => true],
        ]);

        return [
            'success' => true,
            'reply' => $reply,
            'to' => $from,
            'channel' => $channel,
            'unregistered' => true,
        ];
    }

    /**
     * Get conversation history from Redis (fast) or MySQL (fallback)
     */
    protected function getConversationHistory(int $userId, string $phone, string $channel): array
    {
        $redisKey = "messaging_history:{$userId}:{$channel}";

        try {
            // Try Redis first if available
            if (class_exists('Redis') && extension_loaded('redis')) {
                $historyJson = Redis::lrange($redisKey, 0, self::PROMPT_MESSAGE_LIMIT - 1);
                
                if (!empty($historyJson)) {
                    $history = array_map(function($json) {
                        return json_decode($json, true);
                    }, $historyJson);

                    // Reverse to get chronological order (Redis stores newest first)
                    return array_reverse($history);
                }
            }

            // Fallback to MySQL
            $messages = Message::where('user_id', $userId)
                ->where('channel', $channel)
                ->orderBy('created_at', 'desc')
                ->limit(self::PROMPT_MESSAGE_LIMIT)
                ->get();

            if ($messages->isEmpty()) {
                return [];
            }

            // Convert to history format and reverse to chronological
            $history = $messages->reverse()->map(function ($message) {
                return [
                    'role' => $message->role === 'assistant' ? 'model' : 'user',
                    'content' => $message->content,
                    'timestamp' => $message->created_at->toISOString(),
                ];
            })->toArray();

            // Populate Redis cache for next time if available
            if (class_exists('Redis') && extension_loaded('redis')) {
                $this->populateRedisFromMessages($redisKey, $messages);
            }

            return $history;

        } catch (\Exception $e) {
            Log::error('Error getting conversation history: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Store message to database
     */
    protected function storeMessage(array $data): Message
    {
        return Message::create($data);
    }

    /**
     * Add messages to Redis history (for fast retrieval)
     */
    protected function addToRedisHistory(int $userId, string $phone, string $channel, Message $incoming, Message $outgoing): void
    {
        // Skip if Redis is not available
        if (!class_exists('Redis') || !extension_loaded('redis')) {
            return;
        }

        try {
            $redisKey = "messaging_history:{$userId}:{$channel}";

            // Add incoming message
            $incomingData = json_encode([
                'role' => 'user',
                'content' => $incoming->content,
                'timestamp' => $incoming->created_at->toISOString(),
            ]);

            // Add outgoing message
            $outgoingData = json_encode([
                'role' => 'model',
                'content' => $outgoing->content,
                'timestamp' => $outgoing->created_at->toISOString(),
            ]);

            // Push to Redis list (newest first)
            Redis::lpush($redisKey, $outgoingData);
            Redis::lpush($redisKey, $incomingData);

            // Trim to keep only recent messages
            Redis::ltrim($redisKey, 0, self::HISTORY_LIMIT - 1);

            // Set expiration
            Redis::expire($redisKey, self::REDIS_TTL);

        } catch (\Exception $e) {
            Log::error('Error adding to Redis history: ' . $e->getMessage());
        }
    }

    /**
     * Populate Redis from MySQL messages
     */
    protected function populateRedisFromMessages(string $redisKey, $messages): void
    {
        // Skip if Redis is not available
        if (!class_exists('Redis') || !extension_loaded('redis')) {
            return;
        }

        try {
            foreach ($messages as $message) {
                $data = json_encode([
                    'role' => $message->role === 'assistant' ? 'model' : 'user',
                    'content' => $message->content,
                    'timestamp' => $message->created_at->toISOString(),
                ]);

                Redis::lpush($redisKey, $data);
            }

            Redis::ltrim($redisKey, 0, self::HISTORY_LIMIT - 1);
            Redis::expire($redisKey, self::REDIS_TTL);

        } catch (\Exception $e) {
            Log::error('Error populating Redis: ' . $e->getMessage());
        }
    }

    /**
     * Normalize phone number (remove +, -, spaces, etc.)
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $normalized = preg_replace('/[^\d+]/', '', $phone);
        
        // Ensure it starts with country code
        if (!str_starts_with($normalized, '+')) {
            // Assume Indonesian number if no country code
            if (str_starts_with($normalized, '0')) {
                $normalized = '+62' . substr($normalized, 1);
            } elseif (str_starts_with($normalized, '62')) {
                $normalized = '+' . $normalized;
            } else {
                $normalized = '+62' . $normalized;
            }
        }

        return $normalized;
    }

    /**
     * Get message statistics for a user
     */
    public function getMessageStats(int $userId): array
    {
        $totalMessages = Message::where('user_id', $userId)->count();
        $userMessages = Message::where('user_id', $userId)->where('role', 'user')->count();
        $assistantMessages = Message::where('user_id', $userId)->where('role', 'assistant')->count();
        
        $lastMessage = Message::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();

        return [
            'total_messages' => $totalMessages,
            'user_messages' => $userMessages,
            'assistant_messages' => $assistantMessages,
            'last_message_at' => $lastMessage?->created_at,
        ];
    }
}
