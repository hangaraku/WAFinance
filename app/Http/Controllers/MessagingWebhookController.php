<?php

namespace App\Http\Controllers;

use App\Services\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MessagingWebhookController extends Controller
{
    protected MessagingService $messagingService;

    public function __construct(MessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
    }

    /**
     * Handle incoming webhook from n8n/Evolution API/WhatsApp providers
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            // Get raw payload for signature validation
            $rawPayload = $request->getContent();
            $headers = array_change_key_case($request->headers->all(), CASE_LOWER);

            // Flatten header arrays (Laravel wraps headers in arrays)
            $flatHeaders = array_map(function($value) {
                return is_array($value) ? $value[0] : $value;
            }, $headers);

            // Validate webhook signature
            if (!$this->messagingService->validateWebhook($flatHeaders, $rawPayload)) {
                Log::warning('Invalid webhook signature', [
                    'ip' => $request->ip(),
                    'headers' => $flatHeaders,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Invalid signature',
                ], 401);
            }

            // Process the incoming message
            $payload = $request->all();
            $result = $this->messagingService->processIncomingMessage($payload);

            // Return appropriate status code
            $statusCode = $result['success'] ? 200 : 500;

            return response()->json($result, $statusCode);

        } catch (\Exception $e) {
            Log::error('Webhook handling error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Webhook verification endpoint (for some providers that send GET requests)
     */
    public function verify(Request $request): JsonResponse
    {
        // Handle verification challenges (e.g., WhatsApp Business API)
        $challenge = $request->input('hub_challenge') ?? $request->input('hub.challenge');
        $verifyToken = $request->input('hub_verify_token') ?? $request->input('hub.verify_token');
        $expectedToken = config('services.messaging.verify_token');

        // If no expected token is configured, allow verification (development mode)
        if (empty($expectedToken)) {
            return response()->json([
                'challenge' => $challenge,
                'note' => 'Verification token not configured',
            ]);
        }

        if ($verifyToken && $verifyToken === $expectedToken) {
            return response()->json([
                'challenge' => $challenge,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Invalid verification token',
        ], 403);
    }

    /**
     * Get webhook status/health check
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'service' => 'messaging_webhook',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
