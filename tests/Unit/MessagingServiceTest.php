<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Message;
use App\Services\AIService;
use App\Services\MessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MessagingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MessagingService $messagingService;
    protected AIService $aiService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock AIService
        $this->aiService = \Mockery::mock(AIService::class);
        $this->app->instance(AIService::class, $this->aiService);
        
        $this->messagingService = new MessagingService($this->aiService);
    }

    public function test_validate_webhook_with_valid_signature(): void
    {
        Config::set('services.messaging.webhook_secret', 'test-secret');

        $payload = '{"from":"+6281234567890","message":"Hello"}';
        $signature = hash_hmac('sha256', $payload, 'test-secret');
        $headers = ['x-webhook-signature' => $signature];

        $result = $this->messagingService->validateWebhook($headers, $payload);

        $this->assertTrue($result);
    }

    public function test_validate_webhook_with_invalid_signature(): void
    {
        Config::set('services.messaging.webhook_secret', 'test-secret');

        $payload = '{"from":"+6281234567890","message":"Hello"}';
        $headers = ['x-webhook-signature' => 'invalid-signature'];

        $result = $this->messagingService->validateWebhook($headers, $payload);

        $this->assertFalse($result);
    }

    public function test_process_incoming_message_from_registered_user(): void
    {
        $user = User::factory()->create([
            'whatsapp_number' => '+6281234567890',
            'name' => 'Test User',
        ]);

        $this->aiService->shouldReceive('processMessage')
            ->once()
            ->andReturn([
                'response' => 'AI Response',
                'model' => 'gemini-2.0-flash',
            ]);

        $payload = [
            'from' => '+6281234567890',
            'message' => 'What is my balance?',
            'id' => 'msg-12345',
            'channel' => 'whatsapp',
        ];

        $result = $this->messagingService->processIncomingMessage($payload);

        $this->assertTrue($result['success']);
        $this->assertEquals('AI Response', $result['reply']);
        $this->assertEquals('+6281234567890', $result['to']);
        $this->assertEquals($user->id, $result['user']['id']);

        // Check messages were stored
        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'external_id' => 'msg-12345',
            'role' => 'user',
            'content' => 'What is my balance?',
        ]);

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => 'AI Response',
        ]);
    }

    public function test_process_incoming_message_from_unregistered_user(): void
    {
        $payload = [
            'from' => '+6281234567890',
            'message' => 'Hello',
            'id' => 'msg-67890',
            'channel' => 'whatsapp',
        ];

        $result = $this->messagingService->processIncomingMessage($payload);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['unregistered']);
        $this->assertStringContainsString('belum terdaftar', $result['reply']);

        // Check message was stored without user_id
        $this->assertDatabaseHas('messages', [
            'user_id' => null,
            'external_id' => 'msg-67890',
            'role' => 'user',
            'content' => 'Hello',
        ]);
    }

    public function test_duplicate_message_handling(): void
    {
        $user = User::factory()->create([
            'whatsapp_number' => '+6281234567890',
        ]);

        Message::create([
            'user_id' => $user->id,
            'external_id' => 'msg-duplicate',
            'channel' => 'whatsapp',
            'from' => '+6281234567890',
            'role' => 'user',
            'content' => 'Original message',
        ]);

        $payload = [
            'from' => '+6281234567890',
            'message' => 'Duplicate message',
            'id' => 'msg-duplicate',
            'channel' => 'whatsapp',
        ];

        $result = $this->messagingService->processIncomingMessage($payload);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['duplicate'] ?? false);

        // Should only have one message with this external_id
        $this->assertEquals(1, Message::where('external_id', 'msg-duplicate')->count());
    }

    public function test_get_message_stats(): void
    {
        $user = User::factory()->create();

        Message::factory()->create([
            'user_id' => $user->id,
            'role' => 'user',
        ]);

        Message::factory()->create([
            'user_id' => $user->id,
            'role' => 'assistant',
        ]);

        $stats = $this->messagingService->getMessageStats($user->id);

        $this->assertEquals(2, $stats['total_messages']);
        $this->assertEquals(1, $stats['user_messages']);
        $this->assertEquals(1, $stats['assistant_messages']);
        $this->assertNotNull($stats['last_message_at']);
    }
}
