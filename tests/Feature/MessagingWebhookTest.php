<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MessagingWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.messaging.webhook_secret' => 'test-secret']);
    }

    public function test_webhook_status_endpoint(): void
    {
        $response = $this->get('/api/messaging/status');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'service' => 'messaging_webhook',
        ]);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $payload = [
            'from' => '+6281234567890',
            'message' => 'Hello',
        ];

        $response = $this->postJson('/api/messaging/webhook', $payload, [
            'X-Webhook-Signature' => 'invalid-signature',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'error' => 'Invalid signature',
        ]);
    }

    public function test_webhook_accepts_valid_signature_and_processes_message(): void
    {
        $user = User::factory()->create([
            'whatsapp_number' => '+6281234567890',
            'name' => 'Test User',
        ]);

        $payload = [
            'from' => '+6281234567890',
            'message' => 'Hello AI',
            'id' => 'msg-test-123',
            'channel' => 'whatsapp',
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, 'test-secret');

        $response = $this->postJson('/api/messaging/webhook', $payload, [
            'X-Webhook-Signature' => $signature,
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'reply',
            'to',
            'channel',
        ]);

        // Verify message was stored
        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'external_id' => 'msg-test-123',
            'role' => 'user',
            'content' => 'Hello AI',
        ]);
    }

    public function test_webhook_handles_unregistered_user(): void
    {
        $payload = [
            'from' => '+6289999999999',
            'message' => 'Hello',
            'id' => 'msg-unregistered',
            'channel' => 'whatsapp',
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, 'test-secret');

        $response = $this->postJson('/api/messaging/webhook', $payload, [
            'X-Webhook-Signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'unregistered' => true,
        ]);

        $this->assertStringContainsString('belum terdaftar', $response->json('reply'));

        // Verify message was stored without user
        $this->assertDatabaseHas('messages', [
            'user_id' => null,
            'external_id' => 'msg-unregistered',
            'from' => '+6289999999999',
        ]);
    }

    public function test_webhook_verify_endpoint(): void
    {
        // Set config before making the request
        config(['services.messaging.verify_token' => 'verify-token-123']);

        $response = $this->get('/api/messaging/webhook?' . http_build_query([
            'hub.challenge' => 'challenge-value',
            'hub.verify_token' => 'verify-token-123',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'challenge' => 'challenge-value',
        ]);
    }

    public function test_webhook_verify_rejects_invalid_token(): void
    {
        // Set config before making the request
        config(['services.messaging.verify_token' => 'verify-token-123']);

        $response = $this->get('/api/messaging/webhook?' . http_build_query([
            'hub.challenge' => 'challenge-value',
            'hub.verify_token' => 'wrong-token',
        ]));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'Invalid verification token',
        ]);
    }

    public function test_webhook_handles_missing_required_fields(): void
    {
        $payload = [
            'message' => 'Hello', // Missing 'from' field
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, 'test-secret');

        $response = $this->postJson('/api/messaging/webhook', $payload, [
            'X-Webhook-Signature' => $signature,
        ]);

        $response->assertStatus(500); // MessagingService returns error
        $response->assertJson([
            'success' => false,
        ]);
    }
}
