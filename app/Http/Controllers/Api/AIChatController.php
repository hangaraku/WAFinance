<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AIService;
use App\Models\User;

class AIChatController extends Controller
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Process chat message via API
     * This endpoint can be used by WhatsApp, web, or other integrations
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'user_id' => 'required|exists:users,id',
            'context' => 'sometimes|array',
            'platform' => 'sometimes|string|in:web,whatsapp,telegram'
        ]);

        $user = User::findOrFail($request->user_id);
        $message = $request->input('message');
        $context = $request->input('context', []);
        $platform = $request->input('platform', 'web');

        // Add platform context
        $context['platform'] = $platform;
        $context['timestamp'] = now()->toISOString();

        // Process message with AI
        $response = $this->aiService->processMessage($user, $message, $context);

        // Add metadata for API response
        $response['metadata'] = [
            'user_id' => $user->id,
            'platform' => $platform,
            'timestamp' => now()->toISOString(),
            'message_id' => uniqid('msg_', true)
        ];

        return response()->json($response);
    }

    /**
     * Get user's financial summary for AI context
     */
    public function getFinancialSummary(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($request->user_id);
        
        // Get financial context (same as AI service uses)
        $reflection = new \ReflectionClass($this->aiService);
        $method = $reflection->getMethod('getUserFinancialContext');
        $method->setAccessible(true);
        
        $financialContext = $method->invoke($this->aiService, $user);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ],
            'financial_summary' => $financialContext,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Health check for AI service
     */
    public function health()
    {
        try {
            // Test AI service with a simple message
            $testUser = User::first();
            if (!$testUser) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No users found for testing'
                ], 500);
            }

            $response = $this->aiService->processMessage($testUser, 'Hello');
            
            return response()->json([
                'status' => 'healthy',
                'ai_service' => 'operational',
                'model' => 'openai/gpt-3.5-turbo',
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'AI service not responding',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available AI models
     */
    public function getModels()
    {
        $models = $this->aiService->getAvailableModels();
        
        return response()->json([
            'models' => $models,
            'current_model' => 'openai/gpt-3.5-turbo',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Clear conversation history for a user
     */
    public function clearHistory(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($request->user_id);
        $this->aiService->clearConversationHistory($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Conversation history cleared',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get conversation history for a user
     */
    public function getHistory(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($request->user_id);
        $history = $this->aiService->getConversationHistory($user);

        return response()->json([
            'history' => $history,
            'count' => count($history),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Webhook endpoint for WhatsApp integration
     * This will be used when integrating with Evolution API
     */
    public function whatsappWebhook(Request $request)
    {
        // This will be implemented when integrating with WhatsApp
        // For now, just return a placeholder response
        
        return response()->json([
            'status' => 'webhook_received',
            'message' => 'WhatsApp webhook endpoint ready for integration',
            'timestamp' => now()->toISOString()
        ]);
    }
}