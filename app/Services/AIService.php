<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Budget;
use App\Services\AI\Orchestrator;
use App\Services\AI\FunctionRouter;
use App\Services\AI\Providers\GeminiProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected Orchestrator $orchestrator;
    protected FunctionRouter $router;

    public function __construct()
    {
        $apiKey = config('services.google.generative_api_key');
        $model = config('services.google.model', 'gemini-2.0-flash-exp');

        // Fallback if config is missing (for safety during refactor)
        if (empty($apiKey)) {
            Log::warning('Google API Key is missing in configuration.');
        }

        $provider = new GeminiProvider($apiKey ?? '', $model);
        $this->router = new FunctionRouter();
        $this->orchestrator = new Orchestrator($provider, $this->router);
    }

    /**
     * Process a chat message and return AI response
     */
    public function processMessage(User $user, string $message, array $context = []): array
    {
        try {
            // Get conversation history
            $history = $this->getConversationHistory($user);

            // Extract timestamp from context or use current time
            $timestamp = $context['timestamp'] ?? now()->toISOString();

            // Run Orchestrator
            // Orchestrator handles the loop of calling AI -> executing tools -> calling AI
            $responseContent = $this->orchestrator->handle($user, $message, $history, $timestamp);

            // Store the conversation
            $this->storeConversationMessage($user, 'user', $message);
            $this->storeConversationMessage($user, 'model', $responseContent);

            return [
                'response' => $responseContent,
                'action' => null, // Actions are now handled internally by tools
                'error' => false,
                'model' => 'gemini-native',
                'usage' => null,
                'timestamp' => $timestamp
            ];

        } catch (\Exception $e) {
            Log::error('AIService Error: ' . $e->getMessage());
            return [
                'response' => "Maaf, saya mengalami kesalahan saat memproses permintaan Anda. Silakan coba lagi.",
                'action' => null,
                'error' => true,
                'model' => 'gemini-native',
                'usage' => null
            ];
        }
    }

    /**
     * Get conversation history for a user
     */
    public function getConversationHistory(User $user, int $limit = 10): array
    {
        $cacheKey = "ai_conversation_{$user->id}";
        return cache()->get($cacheKey, []);
    }

    /**
     * Store conversation message
     */
    public function storeConversationMessage(User $user, string $role, string $content): void
    {
        $cacheKey = "ai_conversation_{$user->id}";
        $history = cache()->get($cacheKey, []);
        
        $history[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => now()->toISOString()
        ];
        
        // Keep only last 20 messages
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }
        
        // Store for 24 hours
        cache()->put($cacheKey, $history, 86400);
    }

    /**
     * Clear conversation history for a user
     */
    public function clearConversationHistory(User $user): void
    {
        $cacheKey = "ai_conversation_{$user->id}";
        cache()->forget($cacheKey);
    }

    /**
     * Get available models
     */
    public function getAvailableModels(): array
    {
        return [
            'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash (Native Functions)',
        ];
    }

    /**
     * Get user's financial context for AI (Legacy/Controller support)
     * Kept for backward compatibility with AIChatController reflection usage
     */
    private function getUserFinancialContext(User $user): array
    {
        $now = Carbon::now();
        
        // Get recent transactions
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with('category')
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get();

        // Get monthly stats
        $monthlyStats = $this->getMonthlyStats($user, $now);
        
        // Get categories
        $categories = Category::where('user_id', $user->id)->get();
        
        // Get budgets
        $budgets = Budget::where('user_id', $user->id)
            ->with('category')
            ->get();

        return [
            'recent_transactions' => $recentTransactions,
            'monthly_stats' => $monthlyStats,
            'categories' => $categories,
            'budgets' => $budgets,
            'user_name' => $user->name,
            'current_month' => $now->format('F Y')
        ];
    }

    /**
     * Helper for getUserFinancialContext
     */
    private function getMonthlyStats(User $user, Carbon $date): array
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->get();

        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');
        
        $expenseByCategory = $transactions->where('type', 'expense')
            ->groupBy('category_id')
            ->map(function ($transactions) {
                $category = $transactions->first()->category;
                return [
                    'category_name' => $category ? $category->name : 'No Category',
                    'amount' => $transactions->sum('amount'),
                    'count' => $transactions->count()
                ];
            })
            ->sortByDesc('amount')
            ->take(5);

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
            'expense_by_category' => $expenseByCategory,
            'transaction_count' => $transactions->count()
        ];
    }
}
