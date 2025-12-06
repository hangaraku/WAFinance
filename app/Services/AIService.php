<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Budget;
use Carbon\Carbon;

class AIService
{
    private $apiKey;
    private $baseUrl = 'https://openrouter.ai/api/v1';
    private $model = 'google/gemini-flash-1.5'; // Function calling supported model
    
    private $tools = [
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_financial_summary',
                'description' => 'Get a summary of the user\'s financial data for a specific month, date, or year. ALWAYS use this function when users ask about spending, income, expenses, or financial summaries. Examples: "How much did I spend this month?", "What\'s my income for this month?", "Show me my expenses for yesterday".',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'month' => [
                            'type' => 'string',
                            'description' => 'Month in YYYY-MM format (e.g., 2025-09). Can also accept relative references like "bulan ini" (current month), "bulan lalu" (last month). If not provided, uses current month.'
                        ],
                        'date' => [
                            'type' => 'string',
                            'description' => 'Specific date in YYYY-MM-DD format for daily summary. Can also accept relative references like "hari ini" (today), "kemarin" (yesterday). Use this parameter when users ask about specific dates like "8 September", "September 8th", "2025-09-08", etc.'
                        ],
                        'year' => [
                            'type' => 'string',
                            'description' => 'Year in YYYY format for yearly summary. Can also accept relative references like "tahun ini" (current year), "tahun lalu" (last year).'
                        ]
                    ]
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_transactions',
                'description' => 'Get user transactions with optional filtering. ALWAYS use this function when users ask about specific transactions, transaction history, or want to see transaction details. Examples: "What are my transactions?", "Show me my last transactions", "What transactions did I have on [date]?".',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'enum' => ['income', 'expense', 'transfer'],
                            'description' => 'Filter by transaction type'
                        ],
                        'category_id' => [
                            'type' => 'integer',
                            'description' => 'Filter by category ID'
                        ],
                        'start_date' => [
                            'type' => 'string',
                            'description' => 'Start date in YYYY-MM-DD format. Can also accept relative references like "hari ini" (today), "kemarin" (yesterday), "bulan ini" (current month start).'
                        ],
                        'end_date' => [
                            'type' => 'string',
                            'description' => 'End date in YYYY-MM-DD format. Can also accept relative references like "hari ini" (today), "kemarin" (yesterday), "bulan ini" (current month end).'
                        ],
                        'month' => [
                            'type' => 'string',
                            'description' => 'Month in YYYY-MM format. Can also accept relative references like "bulan ini" (current month), "bulan lalu" (last month).'
                        ],
                        'date' => [
                            'type' => 'string',
                            'description' => 'Specific date in YYYY-MM-DD format. Can also accept relative references like "hari ini" (today), "kemarin" (yesterday). Use this for single day queries.'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Maximum number of transactions to return (default: 10)'
                        ]
                    ]
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'add_transaction',
                'description' => 'Add a new transaction (income, expense, or transfer) for the user',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'enum' => ['income', 'expense', 'transfer'],
                            'description' => 'Type of transaction'
                        ],
                        'amount' => [
                            'type' => 'number',
                            'description' => 'Transaction amount (positive number)'
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Transaction description'
                        ],
                        'category_id' => [
                            'type' => 'integer',
                            'description' => 'Category ID for the transaction'
                        ],
                        'account_id' => [
                            'type' => 'integer',
                            'description' => 'Account ID for the transaction'
                        ],
                        'date' => [
                            'type' => 'string',
                            'description' => 'Transaction date in YYYY-MM-DD format. Can also accept relative references like "hari ini" (today), "kemarin" (yesterday). Defaults to today.'
                        ],
                        'to_account_id' => [
                            'type' => 'integer',
                            'description' => 'Destination account ID (required for transfer transactions)'
                        ]
                    ],
                    'required' => ['type', 'amount', 'description', 'category_id', 'account_id']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_accounts',
                'description' => 'Get user\'s accounts for transactions',
                'parameters' => [
                    'type' => 'object',
                    'properties' => []
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_categories',
                'description' => 'Get user\'s transaction categories',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'enum' => ['income', 'expense'],
                            'description' => 'Filter by category type'
                        ]
                    ]
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_budgets',
                'description' => 'Get user\'s budgets and their progress',
                'parameters' => [
                    'type' => 'object',
                    'properties' => []
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_expense_analysis',
                'description' => 'Get detailed analysis of expenses by category',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'month' => [
                            'type' => 'string',
                            'description' => 'Month in YYYY-MM format (e.g., 2025-09). If not provided, uses current month.'
                        ]
                    ]
                ]
            ]
        ]
    ];

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.api_key');
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
        
        // Keep only last 20 messages to prevent cache from growing too large
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
     * Process a chat message and return AI response
     */
    public function processMessage(User $user, string $message, array $context = []): array
    {
        try {
            // Get conversation history
            $conversationHistory = $this->getConversationHistory($user);
            
            // Store user message
            $this->storeConversationMessage($user, 'user', $message);
            
            // Build conversation with function calling support and history
            $conversation = $this->buildConversationWithFunctions($user, $message, $conversationHistory);
            
            // Call OpenRouter API with function calling
            $response = $this->callOpenRouterWithFunctions($conversation);
            
            // Handle tool calls if any
            if (isset($response['choices'][0]['message']['tool_calls'])) {
                $result = $this->handleToolCall($response, $user, $conversation);
                
                // Store AI response
                if (isset($result['response'])) {
                    $this->storeConversationMessage($user, 'assistant', $result['response']);
                }
                
                return $result;
            }
            
            // Parse and return response
            $result = $this->parseResponse($response, $user, $message);
            
            // Store AI response
            if (isset($result['response'])) {
                $this->storeConversationMessage($user, 'assistant', $result['response']);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('AI Service Error: ' . $e->getMessage());
            
            // Check if it's a rate limit error
            if (strpos($e->getMessage(), 'rate-limited') !== false) {
                return [
                    'response' => 'I\'m currently experiencing high demand. Please try again in a few moments.',
                    'action' => null,
                    'error' => false
                ];
            }
            
            return [
                'response' => __('ai.error_message'),
                'action' => null,
                'error' => true,
                'debug' => $e->getMessage()
            ];
        }
    }

    /**
     * Parse relative time references to actual dates
     */
    private function parseRelativeTime(string $timeReference): string
    {
        $now = Carbon::now();
        
        switch (strtolower(trim($timeReference))) {
            case 'hari ini':
            case 'today':
                return $now->format('Y-m-d');
            case 'kemarin':
            case 'yesterday':
                return $now->copy()->subDay()->format('Y-m-d');
            case 'bulan ini':
            case 'this month':
                return $now->format('Y-m');
            case 'bulan lalu':
            case 'last month':
                return $now->copy()->subMonth()->format('Y-m');
            case 'tahun ini':
            case 'this year':
                return $now->format('Y');
            case 'tahun lalu':
            case 'last year':
                return $now->copy()->subYear()->format('Y');
            default:
                // If it's already in a proper format, return as is
                return $timeReference;
        }
    }

    /**
     * Build conversation with function calling support
     */
    private function buildConversationWithFunctions(User $user, string $message, array $context = []): array
    {
        // Get current time context
        $now = Carbon::now();
        $currentDate = $now->format('Y-m-d');
        $currentMonth = $now->format('Y-m');
        $currentYear = $now->format('Y');
        $yesterday = $now->copy()->subDay()->format('Y-m-d');
        $lastMonth = $now->copy()->subMonth()->format('Y-m');
        $lastYear = $now->copy()->subYear()->format('Y');
        
        $systemPrompt = "You are a helpful AI financial assistant for {$user->name}. " .
            "You help users manage their finances, analyze spending, and provide financial advice. " .
            "Be friendly, professional, and helpful. " .
            "CRITICAL: You have DIRECT ACCESS to the user's financial data through function calls. " .
            "You MUST NEVER ask users to provide data or grant access - you already have full access. " .
            "When users ask ANY question about their finances, IMMEDIATELY call the appropriate function:\n" .
            "- 'How much did I spend this month?' → ALWAYS call get_financial_summary with month='bulan ini'\n" .
            "- 'What are my transactions?' → ALWAYS call get_transactions\n" .
            "- 'Show me my accounts' → ALWAYS call get_accounts\n" .
            "- 'What are my categories?' → ALWAYS call get_categories\n" .
            "- 'Add an expense' → ALWAYS call add_transaction (ask for missing details if needed)\n" .
            "RULE: If you don't call a function for financial queries, you are failing. " .
            "Always call functions first, then provide answers based on the real data.\n\n" .
            "CURRENT TIME CONTEXT:\n" .
            "- Today's date: {$currentDate}\n" .
            "- Current month: {$currentMonth}\n" .
            "- Current year: {$currentYear}\n" .
            "- Yesterday's date: {$yesterday}\n" .
            "- Last month: {$lastMonth}\n" .
            "- Last year: {$lastYear}\n\n" .
            "When users use relative time references like 'hari ini' (today), 'kemarin' (yesterday), 'bulan ini' (this month), 'bulan lalu' (last month), 'tahun ini' (this year), etc., " .
            "translate them to the appropriate date ranges based on the current time context above. " .
            "For example:\n" .
            "- 'hari ini' = {$currentDate}\n" .
            "- 'kemarin' = {$yesterday}\n" .
            "- 'bulan ini' = {$currentMonth}\n" .
            "- 'bulan lalu' = {$lastMonth}\n" .
            "- 'tahun ini' = {$currentYear}\n" .
            "- 'tahun lalu' = {$lastYear}";

        $conversation = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ]
        ];

        // Add conversation history if available
        if (!empty($context)) {
            foreach ($context as $msg) {
                if (is_array($msg) && isset($msg['role']) && isset($msg['content'])) {
                    $conversation[] = [
                        'role' => $msg['role'],
                        'content' => $msg['content']
                    ];
                }
            }
        }

        // Add current message
        $conversation[] = [
            'role' => 'user',
            'content' => $message
        ];

        return $conversation;
    }

    /**
     * Call OpenRouter API with function calling support
     */
    private function callOpenRouterWithFunctions(array $conversation): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ])
        ->post($this->baseUrl . '/chat/completions', [
            'model' => $this->model,
            'messages' => $conversation,
            'tools' => $this->tools,
            'tool_choice' => 'auto',
            'max_tokens' => 1000,
            'temperature' => 0.7,
        ]);

        $response->throw();
        return $response->json();
    }

    /**
     * Handle tool calls from AI
     */
    private function handleToolCall(array $response, User $user, array $conversation): array
    {
        $toolCalls = $response['choices'][0]['message']['tool_calls'];
        
        // Add assistant message with tool calls to conversation
        $conversation[] = [
            'role' => 'assistant',
            'content' => $response['choices'][0]['message']['content'],
            'tool_calls' => $toolCalls
        ];

        // Execute each tool call
        foreach ($toolCalls as $toolCall) {
            $functionName = $toolCall['function']['name'];
            $functionArgs = json_decode($toolCall['function']['arguments'], true);

            // Execute the function
            $functionResult = $this->executeFunction($functionName, $functionArgs, $user);

            // Add tool result to conversation
            $conversation[] = [
                'role' => 'tool',
                'tool_call_id' => $toolCall['id'],
                'content' => json_encode($functionResult)
            ];
        }

        // Call API again with tool results
        $finalResponse = $this->callOpenRouterWithFunctions($conversation);

        // Check if AI wants to call more tools
        if (isset($finalResponse['choices'][0]['message']['tool_calls'])) {
            return $this->handleToolCall($finalResponse, $user, $conversation);
        }

        // Return final response
        return $this->parseResponse($finalResponse, $user, '');
    }

    /**
     * Execute function based on name and arguments
     */
    private function executeFunction(string $functionName, array $args, User $user): array
    {
        switch ($functionName) {
            case 'get_financial_summary':
                return $this->getFinancialSummary($user, $args['month'] ?? null, $args['date'] ?? null, $args['year'] ?? null);
            
            case 'get_transactions':
                return $this->getTransactions($user, $args);
            
            case 'add_transaction':
                return $this->addTransaction($user, $args);
            
            case 'get_accounts':
                return $this->getAccounts($user);
            
            case 'get_categories':
                return $this->getCategories($user, $args['type'] ?? null);
            
            case 'get_budgets':
                return $this->getBudgets($user);
            
            case 'get_expense_analysis':
                return $this->getExpenseAnalysis($user, $args['month'] ?? null);
            
            default:
                return ['error' => 'Unknown function: ' . $functionName];
        }
    }

    /**
     * Get user's financial context for AI
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
     * Get monthly financial statistics
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

    /**
     * Build conversation context for AI
     */
    private function buildConversation(User $user, string $message, array $financialContext, array $conversationHistory = []): array
    {
        $systemPrompt = $this->getSystemPrompt($financialContext);
        
        $conversation = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ]
        ];

        // Add conversation history if available
        if (!empty($conversationHistory)) {
            foreach ($conversationHistory as $msg) {
                $conversation[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        }

        // Add current message
        $conversation[] = [
            'role' => 'user',
            'content' => $message
        ];

        return $conversation;
    }

    /**
     * Get system prompt for AI
     */
    private function getSystemPrompt(array $context): string
    {
        $userName = $context['user_name'];
        $currentMonth = $context['current_month'];
        $monthlyStats = $context['monthly_stats'];
        
        $prompt = "You are a helpful AI financial assistant for {$userName}. ";
        $prompt .= "You help users manage their finances, analyze spending, and provide financial advice. ";
        $prompt .= "Be friendly, professional, and helpful.\n\n";
        
        $prompt .= "Current Financial Context for {$currentMonth}:\n";
        $prompt .= "- Total Income: Rp " . number_format($monthlyStats['income']) . "\n";
        $prompt .= "- Total Expenses: Rp " . number_format($monthlyStats['expense']) . "\n";
        $prompt .= "- Balance: Rp " . number_format($monthlyStats['balance']) . "\n";
        $prompt .= "- Total Transactions: " . $monthlyStats['transaction_count'] . "\n\n";
        
        if ($monthlyStats['expense_by_category']->count() > 0) {
            $prompt .= "Top Expense Categories:\n";
            foreach ($monthlyStats['expense_by_category'] as $category) {
                $prompt .= "- {$category['category_name']}: Rp " . number_format($category['amount']) . " ({$category['count']} transactions)\n";
            }
            $prompt .= "\n";
        }
        
        $prompt .= "Available Categories: ";
        $categories = $context['categories']->pluck('name')->join(', ');
        $prompt .= $categories . "\n\n";
        
        $prompt .= "Guidelines:\n";
        $prompt .= "1. Answer questions about spending, income, and financial patterns\n";
        $prompt .= "2. Provide insights and suggestions based on their data\n";
        $prompt .= "3. Be encouraging and supportive\n";
        $prompt .= "4. Use Indonesian Rupiah (Rp) format for amounts\n";
        $prompt .= "5. Keep responses concise but helpful\n";
        $prompt .= "6. If asked to add transactions, explain what information you need\n";
        
        return $prompt;
    }

    /**
     * Call OpenRouter API
     */
    private function callOpenRouter(array $conversation): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'Money Manager AI'
        ])->post($this->baseUrl . '/chat/completions', [
            'model' => $this->model,
            'messages' => $conversation,
            'max_tokens' => 500,
            'temperature' => 0.7,
            'stream' => false
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenRouter API Error: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Parse AI response
     */
    private function parseResponse(array $apiResponse, User $user, string $originalMessage): array
    {
        $aiMessage = $apiResponse['choices'][0]['message']['content'] ?? '';
        
        // Check if AI wants to trigger an action
        $action = $this->detectAction($aiMessage, $originalMessage);
        
        return [
            'response' => $aiMessage,
            'action' => $action,
            'error' => false,
            'model' => $this->model,
            'usage' => $apiResponse['usage'] ?? null
        ];
    }

    /**
     * Detect if AI response contains action triggers
     */
    private function detectAction(string $aiMessage, string $originalMessage): ?array
    {
        // Simple action detection - can be enhanced later
        $message = strtolower($originalMessage);
        
        // Check for transaction creation intent
        if (preg_match('/add|tambah|create|buat.*(\d+).*(?:expense|pengeluaran|income|pemasukan)/i', $message, $matches)) {
            return [
                'type' => 'add_transaction',
                'label' => __('ai.add_transaction'),
                'data' => $this->extractTransactionData($originalMessage)
            ];
        }
        
        return null;
    }

    /**
     * Extract transaction data from message
     */
    private function extractTransactionData(string $message): array
    {
        preg_match('/(\d+)/', $message, $amountMatches);
        $amount = $amountMatches[1] ?? null;
        
        $isIncome = preg_match('/income|pemasukan/i', $message);
        $isExpense = preg_match('/expense|pengeluaran/i', $message);
        
        // Extract description
        $description = $this->extractDescription($message);
        
        return [
            'amount' => $amount,
            'type' => $isIncome ? 'income' : ($isExpense ? 'expense' : 'expense'),
            'description' => $description
        ];
    }

    /**
     * Extract description from message
     */
    private function extractDescription(string $message): string
    {
        $words = explode(' ', $message);
        $description = '';
        
        foreach ($words as $word) {
            if (is_numeric($word)) continue;
            if (in_array(strtolower($word), ['add', 'tambah', 'create', 'buat', 'expense', 'pengeluaran', 'income', 'pemasukan', 'for', 'untuk'])) continue;
            $description .= $word . ' ';
        }
        
        return trim($description) ?: 'AI Generated Transaction';
    }

    /**
     * Get available models (for future use)
     */
    public function getAvailableModels(): array
    {
        return [
            'openai/gpt-3.5-turbo' => 'GPT-3.5 Turbo (Fast & Cost-effective)',
            'openai/gpt-4' => 'GPT-4 (Most Capable)',
            'openai/gpt-4-turbo' => 'GPT-4 Turbo (Fast GPT-4)',
            'anthropic/claude-3-haiku' => 'Claude 3 Haiku (Fast & Cheap)',
            'anthropic/claude-3-sonnet' => 'Claude 3 Sonnet (Balanced)'
        ];
    }

    /**
     * Set model for AI responses
     */
    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    /**
     * Function: Get financial summary for a specific month
     */
    private function getFinancialSummary(User $user, ?string $month = null, ?string $date = null, ?string $year = null): array
    {
        // Parse relative time references
        if ($month) {
            $month = $this->parseRelativeTime($month);
        }
        if ($date) {
            $date = $this->parseRelativeTime($date);
        }
        if ($year) {
            $year = $this->parseRelativeTime($year);
        }

        // Handle different time periods
        if ($date) {
            // Daily summary
            $targetDate = Carbon::createFromFormat('Y-m-d', $date);
            $startOfDay = $targetDate->copy()->startOfDay();
            $endOfDay = $targetDate->copy()->endOfDay();

            $transactions = Transaction::where('user_id', $user->id)
                ->whereBetween('transaction_date', [$startOfDay, $endOfDay])
                ->get();

            $income = $transactions->where('type', 'income')->sum('amount');
            $expense = $transactions->where('type', 'expense')->sum('amount');
            $transfer = $transactions->where('type', 'transfer')->sum('amount');

            return [
                'period' => $targetDate->format('l, F j, Y'),
                'income' => $income,
                'expense' => $expense,
                'transfer' => $transfer,
                'balance' => $income - $expense,
                'transaction_count' => $transactions->count(),
            ];
        } elseif ($year) {
            // Yearly summary
            $targetYear = Carbon::createFromFormat('Y', $year);
            $startOfYear = $targetYear->copy()->startOfYear();
            $endOfYear = $targetYear->copy()->endOfYear();

            $transactions = Transaction::where('user_id', $user->id)
                ->whereBetween('transaction_date', [$startOfYear, $endOfYear])
                ->get();

            $income = $transactions->where('type', 'income')->sum('amount');
            $expense = $transactions->where('type', 'expense')->sum('amount');
            $transfer = $transactions->where('type', 'transfer')->sum('amount');

            return [
                'period' => $targetYear->format('Y'),
                'income' => $income,
                'expense' => $expense,
                'transfer' => $transfer,
                'balance' => $income - $expense,
                'transaction_count' => $transactions->count(),
            ];
        } else {
            // Monthly summary (default)
            $targetMonth = $month ? Carbon::createFromFormat('Y-m', $month) : Carbon::now();
            $startOfMonth = $targetMonth->copy()->startOfMonth();
            $endOfMonth = $targetMonth->copy()->endOfMonth();

            $transactions = Transaction::where('user_id', $user->id)
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->get();

            $income = $transactions->where('type', 'income')->sum('amount');
            $expense = $transactions->where('type', 'expense')->sum('amount');
            $transfer = $transactions->where('type', 'transfer')->sum('amount');

            return [
                'period' => $targetMonth->format('F Y'),
                'income' => $income,
                'expense' => $expense,
                'transfer' => $transfer,
                'balance' => $income - $expense,
                'transaction_count' => $transactions->count(),
                'income_count' => $transactions->where('type', 'income')->count(),
                'expense_count' => $transactions->where('type', 'expense')->count(),
                'transfer_count' => $transactions->where('type', 'transfer')->count()
            ];
        }
    }

    /**
     * Function: Get transactions with filtering
     */
    private function getTransactions(User $user, array $args): array
    {
        $query = Transaction::where('user_id', $user->id)
            ->with('category', 'account');

        if (isset($args['type'])) {
            $query->where('type', $args['type']);
        }

        if (isset($args['category_id'])) {
            $query->where('category_id', $args['category_id']);
        }

        // Handle month parameter
        if (isset($args['month'])) {
            $month = $this->parseRelativeTime($args['month']);
            $date = Carbon::createFromFormat('Y-m', $month);
            $query->whereBetween('transaction_date', [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth()
            ]);
        }

        // Handle date range parameters
        if (isset($args['start_date'])) {
            $startDate = $this->parseRelativeTime($args['start_date']);
            $query->where('transaction_date', '>=', Carbon::parse($startDate));
        }

        if (isset($args['end_date'])) {
            $endDate = $this->parseRelativeTime($args['end_date']);
            $query->where('transaction_date', '<=', Carbon::parse($endDate));
        }

        // Handle specific date parameter (for single day queries)
        if (isset($args['date'])) {
            $date = $this->parseRelativeTime($args['date']);
            $query->whereDate('transaction_date', $date);
        }

        $limit = $args['limit'] ?? 10;
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->limit($limit)
            ->get();

        return $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'description' => $transaction->description,
                'date' => Carbon::parse($transaction->transaction_date)->format('Y-m-d'),
                'category' => $transaction->category ? $transaction->category->name : 'No Category',
                'account' => $transaction->account ? $transaction->account->name : 'No Account',
            ];
        })->toArray();
    }

    /**
     * Function: Add a new transaction
     */
    private function addTransaction(User $user, array $args): array
    {
        try {
            // Parse date if provided
            $date = isset($args['date']) ? $this->parseRelativeTime($args['date']) : Carbon::now()->format('Y-m-d');
            
            // Validate required fields
            $requiredFields = ['type', 'amount', 'description', 'category_id', 'account_id'];
            foreach ($requiredFields as $field) {
                if (!isset($args[$field])) {
                    return ['error' => "Missing required field: {$field}"];
                }
            }

            // Validate transaction type
            if (!in_array($args['type'], ['income', 'expense', 'transfer'])) {
                return ['error' => 'Invalid transaction type. Must be income, expense, or transfer.'];
            }

            // Validate amount
            if ($args['amount'] <= 0) {
                return ['error' => 'Amount must be greater than 0'];
            }

            // For transfer transactions, validate to_account_id
            if ($args['type'] === 'transfer' && !isset($args['to_account_id'])) {
                return ['error' => 'to_account_id is required for transfer transactions'];
            }

            // Create the transaction
            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->type = $args['type'];
            $transaction->amount = $args['amount'];
            $transaction->description = $args['description'];
            $transaction->category_id = $args['category_id'];
            $transaction->account_id = $args['account_id'];
            $transaction->transaction_date = $date;
            
            if ($args['type'] === 'transfer' && isset($args['to_account_id'])) {
                $transaction->to_account_id = $args['to_account_id'];
            }

            $transaction->save();

            // Load relationships for response
            $transaction->load('category', 'account');

            return [
                'success' => true,
                'message' => 'Transaction added successfully',
                'transaction' => [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'date' => Carbon::parse($transaction->transaction_date)->format('Y-m-d'),
                    'category' => $transaction->category ? $transaction->category->name : 'No Category',
                    'account' => $transaction->account ? $transaction->account->name : 'No Account',
                ]
            ];

        } catch (\Exception $e) {
            return ['error' => 'Failed to add transaction: ' . $e->getMessage()];
        }
    }

    /**
     * Function: Get user's accounts
     */
    private function getAccounts(User $user): array
    {
        $accounts = \App\Models\Account::where('user_id', $user->id)->get();
        
        return $accounts->map(function ($account) {
            return [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'balance' => $account->balance,
            ];
        })->toArray();
    }

    /**
     * Function: Get categories
     */
    private function getCategories(User $user, ?string $type = null): array
    {
        $query = Category::where('user_id', $user->id);

        if ($type) {
            $query->where('type', $type);
        }

        $categories = $query->orderBy('name')->get();

        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
                'color' => $category->color,
            ];
        })->toArray();
    }

    /**
     * Function: Get budgets
     */
    private function getBudgets(User $user): array
    {
        $budgets = Budget::where('user_id', $user->id)
            ->with('category')
            ->get();

        return $budgets->map(function ($budget) {
            return [
                'id' => $budget->id,
                'category_name' => $budget->category ? $budget->category->name : 'No Category',
                'amount' => $budget->amount,
                'spent' => $budget->spent,
                'remaining' => $budget->amount - $budget->spent,
                'progress_percentage' => $budget->amount > 0 ? round(($budget->spent / $budget->amount) * 100, 2) : 0,
                'period' => $budget->period,
                'start_date' => $budget->start_date,
                'end_date' => $budget->end_date,
            ];
        })->toArray();
    }

    /**
     * Function: Get expense analysis by category
     */
    private function getExpenseAnalysis(User $user, ?string $month = null): array
    {
        $date = $month ? Carbon::createFromFormat('Y-m', $month) : Carbon::now();
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $expenses = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->with('category')
            ->get();

        $totalExpense = $expenses->sum('amount');
        $expenseByCategory = $expenses->groupBy('category_id')
            ->map(function ($transactions) use ($totalExpense) {
                $category = $transactions->first()->category;
                $amount = $transactions->sum('amount');
                return [
                    'category_name' => $category ? $category->name : 'No Category',
                    'amount' => $amount,
                    'count' => $transactions->count(),
                    'percentage' => $totalExpense > 0 ? round(($amount / $totalExpense) * 100, 2) : 0,
                ];
            })
            ->sortByDesc('amount')
            ->values();

        return [
            'month' => $date->format('F Y'),
            'total_expense' => $totalExpense,
            'total_transactions' => $expenses->count(),
            'expense_by_category' => $expenseByCategory->toArray(),
        ];
    }
}
