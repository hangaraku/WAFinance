<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Budget;
use App\Models\Account;
use App\Models\Goal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FunctionRouter
{
    public function __construct()
    {
        // No dependencies - AI handles all parsing
    }

    public function getFunctions(): array
    {
        return [
            [
                'name' => 'add_transaction',
                'description' => 'Add a single transaction (income, expense, or transfer). YOU must parse the amount, deduce the date from context, and have already called get_categories to determine category_id.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'enum' => ['income', 'expense', 'transfer'],
                            'description' => 'Transaction type. Deduce from context: "bought"/"bayar"/"spent" = expense, "received"/"dapat"/"gaji" = income, "transfer"/"kirim" = transfer'
                        ],
                        'amount' => [
                            'type' => 'number',
                            'description' => 'Numeric amount (e.g., 5000, not "5rb"). Parse any format: "5rb"→5000, "2.5jt"→2500000, "Rp 50.000"→50000, "$100"→100'
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Brief description of the transaction (e.g., "Coffee at Starbucks", "Lunch", "Monthly salary")'
                        ],
                        'category_id' => [
                            'type' => 'integer',
                            'description' => 'Category ID. MUST call get_categories first to find matching category, then supply the ID here.'
                        ],
                        'account_id' => [
                            'type' => 'integer',
                            'description' => 'Account ID. Call get_accounts to get list. Use default account if user doesn\'t specify.'
                        ],
                        'date' => [
                            'type' => 'string',
                            'description' => 'Transaction date in YYYY-MM-DD format. Calculate from the provided timestamp. Examples: "today"→use current date, "yesterday"→subtract 1 day, "2 days ago"→subtract 2 days, "pagi tadi"→today.'
                        ],
                        'time' => [
                            'type' => 'string',
                            'description' => 'Transaction time in HH:mm:ss format (24-hour). If user mentions time of day: "pagi"→07:00:00, "siang"→13:00:00, "sore"→17:00:00, "malam"→20:00:00. Otherwise use current time from timestamp.'
                        ],
                        'to_account_id' => [
                            'type' => 'integer',
                            'description' => 'Required only for transfer type. Destination account ID.'
                        ]
                    ],
                    'required' => ['type', 'amount', 'description', 'category_id', 'account_id', 'date']
                ]
            ],
            [
                'name' => 'add_multiple_transactions',
                'description' => 'Add multiple transactions at once (batch operation). Use when user mentions multiple transactions in one message. YOU must parse each amount, deduce dates, and match categories.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'transactions' => [
                            'type' => 'array',
                            'description' => 'Array of transactions to add. Each must have all required fields.',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'type' => [
                                        'type' => 'string',
                                        'enum' => ['income', 'expense', 'transfer'],
                                        'description' => 'Transaction type'
                                    ],
                                    'amount' => [
                                        'type' => 'number',
                                        'description' => 'Parsed numeric amount (e.g., 5000 not "5rb")'
                                    ],
                                    'description' => [
                                        'type' => 'string',
                                        'description' => 'Transaction description'
                                    ],
                                    'category_id' => [
                                        'type' => 'integer',
                                        'description' => 'Category ID (obtained from get_categories)'
                                    ],
                                    'account_id' => [
                                        'type' => 'integer',
                                        'description' => 'Account ID (obtained from get_accounts)'
                                    ],
                                    'date' => [
                                        'type' => 'string',
                                        'description' => 'Date in YYYY-MM-DD format (calculated from timestamp)'
                                    ],
                                    'time' => [
                                        'type' => 'string',
                                        'description' => 'Time in HH:mm:ss format (24-hour)'
                                    ]
                                ],
                                'required' => ['type', 'amount', 'description', 'category_id', 'account_id', 'date']
                            ]
                        ]
                    ],
                    'required' => ['transactions']
                ]
            ],
            [
                'name' => 'get_financial_summary',
                'description' => 'Get financial summary (income, expenses, balance) for a period. Use when users ask about spending, earnings, or financial overview.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'month' => [
                            'type' => 'string',
                            'description' => 'Month in YYYY-MM format (e.g., 2025-12). Calculate from timestamp: "this month"→current month, "last month"→subtract 1 month, "bulan ini"→current month.'
                        ],
                        'date' => [
                            'type' => 'string',
                            'description' => 'Specific date in YYYY-MM-DD format for daily summary. Calculate from timestamp: "today"→current date, "yesterday"→subtract 1 day, "kemarin"→yesterday.'
                        ],
                        'year' => [
                            'type' => 'string',
                            'description' => 'Year in YYYY format for yearly summary. Calculate from timestamp: "this year"→current year, "last year"→subtract 1 year, "tahun ini"→current year.'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'get_transactions',
                'description' => 'Get user transactions with optional filtering. ALWAYS use this function when users ask about specific transactions, transaction history, or want to see transaction details. Use this for queries about specific account spending (e.g., "pengeluaran dari Cash").',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'enum' => ['income', 'expense', 'transfer'],
                            'description' => 'Filter by transaction type'
                        ],
                        'account_id' => [
                            'type' => 'integer',
                            'description' => 'Filter by account ID. Use this when user asks about spending from specific account (e.g., "pengeluaran Cash", "expenses from BCA"). Call get_accounts first to find the account ID by name.'
                        ],
                        'category_id' => [
                            'type' => 'integer',
                            'description' => 'Filter by category ID'
                        ],
                        'start_date' => [
                            'type' => 'string',
                            'description' => 'Start date in YYYY-MM-DD format. Can also accept relative references like "hari ini" (today), "kemarin" (yesterday).'
                        ],
                        'end_date' => [
                            'type' => 'string',
                            'description' => 'End date in YYYY-MM-DD format. Can also accept relative references like "hari ini" (today), "kemarin" (yesterday).'
                        ],
                        'month' => [
                            'type' => 'string',
                            'description' => 'Month in YYYY-MM format. Can also accept relative references like "bulan ini" (current month).'
                        ],
                        'date' => [
                            'type' => 'string',
                            'description' => 'Specific date in YYYY-MM-DD format. Can also accept relative references like "hari ini" (today).'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Maximum number of transactions to return (default: 10)'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'get_accounts',
                'description' => 'Get user\'s accounts with balances. Use this when user asks about account balances, savings, or available funds.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => (object)[]
                ]
            ],
            [
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
            ],
            [
                'name' => 'get_budgets',
                'description' => 'Get user\'s budgets and their progress. Use when user asks about budget status, spending limits, or overspending.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'month' => [
                            'type' => 'integer',
                            'description' => 'Month number (1-12). Defaults to current month.'
                        ],
                        'year' => [
                            'type' => 'integer',
                            'description' => 'Year (e.g., 2025). Defaults to current year.'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'create_budget',
                'description' => 'Create or update a budget for a category. Use when user wants to set spending limits.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'category_id' => [
                            'type' => 'integer',
                            'description' => 'Category ID for the budget'
                        ],
                        'amount' => [
                            'type' => 'number',
                            'description' => 'Budget amount (positive number)'
                        ],
                        'month' => [
                            'type' => 'integer',
                            'description' => 'Month number (1-12). Defaults to current month.'
                        ],
                        'year' => [
                            'type' => 'integer',
                            'description' => 'Year (e.g., 2025). Defaults to current year.'
                        ]
                    ],
                    'required' => ['category_id', 'amount']
                ]
            ],
            [
                'name' => 'get_goals',
                'description' => 'Get user\'s savings goals and their progress. Use when user asks about savings targets or goal completion.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'include_completed' => [
                            'type' => 'boolean',
                            'description' => 'Whether to include completed goals. Defaults to false.'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'create_goal',
                'description' => 'Create a new savings goal. Use when user wants to set a savings target.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Goal name (e.g., "Emergency Fund", "Vacation")'
                        ],
                        'target_amount' => [
                            'type' => 'number',
                            'description' => 'Target amount to save'
                        ],
                        'target_date' => [
                            'type' => 'string',
                            'description' => 'Target date in YYYY-MM-DD format'
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Optional description for the goal'
                        ]
                    ],
                    'required' => ['name', 'target_amount']
                ]
            ],
            [
                'name' => 'update_goal_progress',
                'description' => 'Update progress towards a savings goal. Use when user saves money towards a goal.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'goal_id' => [
                            'type' => 'integer',
                            'description' => 'Goal ID to update'
                        ],
                        'amount' => [
                            'type' => 'number',
                            'description' => 'Amount to add to current progress (positive number)'
                        ]
                    ],
                    'required' => ['goal_id', 'amount']
                ]
            ],
            [
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
            ],
            [
                'name' => 'get_complete_financial_overview',
                'description' => 'Get a complete financial overview including accounts, budgets, goals, and recent activity. Use this for comprehensive queries about financial status.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => (object)[]
                ]
            ]
        ];
    }

    public function execute(string $functionName, array $args, User $user): array
    {
        Log::info("Executing function: {$functionName}", ['args' => $args, 'user_id' => $user->id]);

        try {
            switch ($functionName) {
                case 'add_transaction':
                    return $this->addTransaction($user, $args);
                
                case 'add_multiple_transactions':
                    return $this->addMultipleTransactions($user, $args['transactions']);
                
                case 'get_financial_summary':
                    return $this->getFinancialSummary($user, $args['month'] ?? null, $args['date'] ?? null, $args['year'] ?? null);
                
                case 'get_transactions':
                    return $this->getTransactions($user, $args);
                
                case 'get_accounts':
                    return $this->getAccounts($user);
                
                case 'get_categories':
                    return $this->getCategories($user, $args['type'] ?? null);
                
                case 'get_budgets':
                    return $this->getBudgets($user, $args['month'] ?? null, $args['year'] ?? null);
                
                case 'create_budget':
                    return $this->createBudget($user, $args);
                
                case 'get_goals':
                    return $this->getGoals($user, $args['include_completed'] ?? false);
                
                case 'create_goal':
                    return $this->createGoal($user, $args);
                
                case 'update_goal_progress':
                    return $this->updateGoalProgress($user, $args['goal_id'], $args['amount']);
                
                case 'get_expense_analysis':
                    return $this->getExpenseAnalysis($user, $args['month'] ?? null);
                
                case 'get_complete_financial_overview':
                    return $this->getCompleteFinancialOverview($user);
                
                default:
                    return ['error' => 'Unknown function: ' . $functionName];
            }
        } catch (\Exception $e) {
            Log::error("Error executing function {$functionName}: " . $e->getMessage());
            return ['error' => 'An error occurred while executing the function: ' . $e->getMessage()];
        }
    }

    private function parseDateParam(?string $param): ?string
    {
        // AI provides ISO format dates, just return as-is
        return $param;
    }

    private function getFinancialSummary(User $user, ?string $month = null, ?string $date = null, ?string $year = null): array
    {
        $month = $this->parseDateParam($month);
        $date = $this->parseDateParam($date);
        $year = $this->parseDateParam($year);

        if ($date) {
            $targetDate = Carbon::parse($date);
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
            $targetYear = Carbon::parse($year); // Assuming YYYY format parses correctly or handled by DateParser
            // If DateParser returns Y-m-d, we might need to adjust. 
            // Assuming DateParser handles "2025" -> "2025-01-01" or similar if it's just a year.
            // Let's assume input is YYYY or handled.
            // Actually DateParser returns Y-m-d. If input is just year, we need to handle it.
            // But let's trust DateParser or the input for now.
            
            // If $year is YYYY-MM-DD from DateParser, we extract year.
            $targetYear = Carbon::parse($year);
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
            $targetMonth = $month ? Carbon::parse($month) : Carbon::now();
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

    private function getTransactions(User $user, array $args): array
    {
        $query = Transaction::where('user_id', $user->id)
            ->with('category', 'account');

        if (isset($args['type'])) {
            $query->where('type', $args['type']);
        }

        if (isset($args['account_id'])) {
            $query->where('account_id', $args['account_id']);
        }

        if (isset($args['category_id'])) {
            $query->where('category_id', $args['category_id']);
        }

        if (isset($args['month'])) {
            $month = $this->parseDateParam($args['month']);
            $date = Carbon::parse($month);
            $query->whereBetween('transaction_date', [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth()
            ]);
        }

        if (isset($args['start_date'])) {
            $startDate = $this->parseDateParam($args['start_date']);
            $query->where('transaction_date', '>=', Carbon::parse($startDate));
        }

        if (isset($args['end_date'])) {
            $endDate = $this->parseDateParam($args['end_date']);
            $query->where('transaction_date', '<=', Carbon::parse($endDate));
        }

        if (isset($args['date'])) {
            $date = $this->parseDateParam($args['date']);
            $query->whereDate('transaction_date', $date);
        }

        $limit = $args['limit'] ?? 100;
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->limit($limit)
            ->get();

        $transactionList = $transactions->map(function ($transaction) {
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

        // Add summary statistics
        $summary = [
            'total_count' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'income_total' => $transactions->where('type', 'income')->sum('amount'),
            'expense_total' => $transactions->where('type', 'expense')->sum('amount'),
            'by_category' => [],
        ];

        // Group by category
        $grouped = $transactions->groupBy(function($t) {
            return $t->category ? $t->category->name : 'No Category';
        });
        foreach ($grouped as $catName => $catTrans) {
            $summary['by_category'][$catName] = [
                'count' => $catTrans->count(),
                'total' => $catTrans->sum('amount'),
            ];
        }

        return [
            'transactions' => $transactionList,
            'summary' => $summary,
        ];
    }

    private function addTransaction(User $user, array $args): array
    {
        $requiredFields = ['type', 'amount', 'description', 'category_id', 'account_id', 'date'];
        foreach ($requiredFields as $field) {
            if (!isset($args[$field])) {
                return ['error' => "Missing required field: {$field}"];
            }
        }

        if (!in_array($args['type'], ['income', 'expense', 'transfer'])) {
            return ['error' => 'Invalid transaction type. Must be income, expense, or transfer.'];
        }

        if ($args['amount'] <= 0) {
            return ['error' => 'Amount must be greater than 0'];
        }

        if ($args['type'] === 'transfer' && !isset($args['to_account_id'])) {
            return ['error' => 'to_account_id is required for transfer transactions'];
        }

        // Validate category belongs to user
        $category = Category::where('id', $args['category_id'])
            ->where('user_id', $user->id)
            ->first();
            
        if (!$category) {
            return ['error' => "Invalid category_id {$args['category_id']}. Category does not exist or does not belong to this user."];
        }
        
        // Validate account belongs to user
        $account = Account::where('id', $args['account_id'])
            ->where('user_id', $user->id)
            ->first();
            
        if (!$account) {
            return ['error' => "Invalid account_id {$args['account_id']}. Account does not exist or does not belong to this user."];
        }

        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->type = $args['type'];
        $transaction->amount = $args['amount'];
        $transaction->description = $args['description'];
        $transaction->category_id = $args['category_id'];
        $transaction->account_id = $args['account_id'];
        $transaction->transaction_date = $args['date'];
        
        // Set transaction time if provided, otherwise use current time
        if (isset($args['time'])) {
            $transaction->transaction_time = $args['date'] . ' ' . $args['time'];
        } else {
            $transaction->transaction_time = Carbon::now();
        }
        
        if ($args['type'] === 'transfer' && isset($args['to_account_id'])) {
            $transaction->transfer_account_id = $args['to_account_id'];
        }

        $transaction->save();
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
                'time' => isset($args['time']) ? $args['time'] : Carbon::parse($transaction->transaction_time)->format('H:i:s'),
                'category' => $transaction->category ? $transaction->category->name : 'No Category',
                'account' => $transaction->account ? $transaction->account->name : 'No Account',
            ]
        ];
    }

    private function getAccounts(User $user): array
    {
        $accounts = Account::where('user_id', $user->id)->get();
        return $accounts->map(function ($account) {
            return [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'balance' => $account->balance,
            ];
        })->toArray();
    }

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

    private function getBudgets(User $user, ?int $month = null, ?int $year = null): array
    {
        $targetMonth = $month ?? Carbon::now()->month;
        $targetYear = $year ?? Carbon::now()->year;
        
        $budgets = Budget::where('user_id', $user->id)
            ->where('month', $targetMonth)
            ->where('year', $targetYear)
            ->with('category')
            ->get();

        // Calculate actual spending for each budget category
        $startOfMonth = Carbon::create($targetYear, $targetMonth, 1)->startOfMonth();
        $endOfMonth = Carbon::create($targetYear, $targetMonth, 1)->endOfMonth();

        return $budgets->map(function ($budget) use ($user, $startOfMonth, $endOfMonth) {
            $spent = Transaction::where('user_id', $user->id)
                ->where('type', 'expense')
                ->where('category_id', $budget->category_id)
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->sum('amount');
            
            $remaining = $budget->amount - $spent;
            $percentage = $budget->amount > 0 ? round(($spent / $budget->amount) * 100, 2) : 0;
            
            return [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'category_name' => $budget->category ? $budget->category->name : 'No Category',
                'budget_amount' => $budget->amount,
                'spent' => $spent,
                'remaining' => $remaining,
                'progress_percentage' => $percentage,
                'is_over_budget' => $remaining < 0,
                'month' => $budget->month,
                'year' => $budget->year,
            ];
        })->toArray();
    }

    private function createBudget(User $user, array $args): array
    {
        $month = $args['month'] ?? Carbon::now()->month;
        $year = $args['year'] ?? Carbon::now()->year;
        
        // Check if category exists
        $category = Category::where('id', $args['category_id'])
            ->where('user_id', $user->id)
            ->first();
            
        if (!$category) {
            return ['error' => 'Category not found'];
        }
        
        // Update or create budget
        $budget = Budget::updateOrCreate(
            [
                'user_id' => $user->id,
                'category_id' => $args['category_id'],
                'month' => $month,
                'year' => $year,
            ],
            [
                'amount' => $args['amount'],
            ]
        );
        
        return [
            'success' => true,
            'message' => 'Budget created/updated successfully',
            'budget' => [
                'id' => $budget->id,
                'category_name' => $category->name,
                'amount' => $budget->amount,
                'month' => $month,
                'year' => $year,
            ]
        ];
    }

    private function getGoals(User $user, bool $includeCompleted = false): array
    {
        $query = Goal::where('user_id', $user->id);
        
        if (!$includeCompleted) {
            $query->where('is_completed', false);
        }
        
        $goals = $query->orderBy('target_date', 'asc')->get();
        
        return $goals->map(function ($goal) {
            $progress = $goal->target_amount > 0 
                ? round(($goal->current_amount / $goal->target_amount) * 100, 2) 
                : 0;
            
            $remaining = $goal->target_amount - $goal->current_amount;
            $daysLeft = $goal->target_date ? Carbon::now()->diffInDays(Carbon::parse($goal->target_date), false) : null;
            
            return [
                'id' => $goal->id,
                'name' => $goal->name,
                'description' => $goal->description,
                'target_amount' => $goal->target_amount,
                'current_amount' => $goal->current_amount,
                'remaining' => $remaining,
                'progress_percentage' => $progress,
                'target_date' => $goal->target_date ? Carbon::parse($goal->target_date)->format('Y-m-d') : null,
                'days_left' => $daysLeft,
                'is_completed' => $goal->is_completed,
                'monthly_required' => $daysLeft > 0 ? round($remaining / max(1, ceil($daysLeft / 30)), 2) : null,
            ];
        })->toArray();
    }

    private function createGoal(User $user, array $args): array
    {
        $goal = new Goal();
        $goal->user_id = $user->id;
        $goal->name = $args['name'];
        $goal->target_amount = $args['target_amount'];
        $goal->current_amount = 0;
        $goal->description = $args['description'] ?? null;
        $goal->target_date = isset($args['target_date']) ? Carbon::parse($args['target_date']) : null;
        $goal->is_completed = false;
        $goal->save();
        
        return [
            'success' => true,
            'message' => 'Goal created successfully',
            'goal' => [
                'id' => $goal->id,
                'name' => $goal->name,
                'target_amount' => $goal->target_amount,
                'target_date' => $goal->target_date ? Carbon::parse($goal->target_date)->format('Y-m-d') : null,
            ]
        ];
    }

    private function updateGoalProgress(User $user, int $goalId, float $amount): array
    {
        $goal = Goal::where('id', $goalId)
            ->where('user_id', $user->id)
            ->first();
            
        if (!$goal) {
            return ['error' => 'Goal not found'];
        }
        
        $goal->current_amount += $amount;
        
        // Check if goal is completed
        if ($goal->current_amount >= $goal->target_amount) {
            $goal->is_completed = true;
        }
        
        $goal->save();
        
        $progress = $goal->target_amount > 0 
            ? round(($goal->current_amount / $goal->target_amount) * 100, 2) 
            : 0;
        
        return [
            'success' => true,
            'message' => $goal->is_completed 
                ? 'Congratulations! Goal completed!' 
                : 'Goal progress updated',
            'goal' => [
                'id' => $goal->id,
                'name' => $goal->name,
                'current_amount' => $goal->current_amount,
                'target_amount' => $goal->target_amount,
                'progress_percentage' => $progress,
                'is_completed' => $goal->is_completed,
            ]
        ];
    }

    private function addMultipleTransactions(User $user, array $transactions): array
    {
        $results = [];
        $successCount = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($transactions as $txData) {
                // Validate category belongs to user
                $category = Category::where('id', $txData['category_id'])
                    ->where('user_id', $user->id)
                    ->first();
                    
                if (!$category) {
                    throw new \Exception("Invalid category_id {$txData['category_id']} for user {$user->id}. Category does not exist or does not belong to this user.");
                }
                
                // Validate account belongs to user
                $account = Account::where('id', $txData['account_id'])
                    ->where('user_id', $user->id)
                    ->first();
                    
                if (!$account) {
                    throw new \Exception("Invalid account_id {$txData['account_id']} for user {$user->id}. Account does not exist or does not belong to this user.");
                }
                
                $transaction = new Transaction();
                $transaction->user_id = $user->id;
                $transaction->type = $txData['type'];
                $transaction->amount = $txData['amount'];
                $transaction->description = $txData['description'];
                $transaction->category_id = $txData['category_id'];
                $transaction->account_id = $txData['account_id'];
                $transaction->transaction_date = $txData['date'];
                
                // Set transaction time if provided
                if (isset($txData['time'])) {
                    $transaction->transaction_time = $txData['date'] . ' ' . $txData['time'];
                } else {
                    $transaction->transaction_time = Carbon::now();
                }
                
                if ($txData['type'] === 'transfer' && isset($txData['to_account_id'])) {
                    $transaction->transfer_account_id = $txData['to_account_id'];
                }
                
                $transaction->save();
                $transaction->load('category', 'account');
                
                $results[] = [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'date' => Carbon::parse($transaction->transaction_date)->format('Y-m-d'),
                    'time' => isset($txData['time']) ? $txData['time'] : Carbon::parse($transaction->transaction_time)->format('H:i:s'),
                    'category' => $transaction->category ? $transaction->category->name : 'No Category',
                    'account' => $transaction->account ? $transaction->account->name : 'No Account',
                ];
                $successCount++;
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => "Successfully added {$successCount} transaction(s)",
                'transactions' => $results,
                'count' => $successCount,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error adding multiple transactions: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Failed to add transactions: ' . $e->getMessage(),
                'count' => 0,
            ];
        }
    }

    private function getCompleteFinancialOverview(User $user): array
    {
        $now = Carbon::now();
        
        // Get accounts with balances
        $accounts = $this->getAccounts($user);
        $totalBalance = array_sum(array_column($accounts, 'balance'));
        
        // Get current month financial summary
        $monthlyStats = $this->getFinancialSummary($user, $now->format('Y-m'), null, null);
        
        // Get budgets for current month
        $budgets = $this->getBudgets($user, $now->month, $now->year);
        $overBudgetCount = count(array_filter($budgets, fn($b) => $b['is_over_budget']));
        
        // Get active goals
        $goals = $this->getGoals($user, false);
        $totalGoalsProgress = count($goals) > 0 
            ? round(array_sum(array_column($goals, 'progress_percentage')) / count($goals), 2) 
            : 0;
        
        // Get recent transactions
        $recentTransactions = $this->getTransactions($user, ['limit' => 5]);
        
        return [
            'overview_date' => $now->format('l, F j, Y'),
            'accounts' => [
                'list' => $accounts,
                'total_balance' => $totalBalance,
                'count' => count($accounts),
            ],
            'monthly_summary' => $monthlyStats,
            'budgets' => [
                'list' => $budgets,
                'count' => count($budgets),
                'over_budget_count' => $overBudgetCount,
            ],
            'goals' => [
                'list' => $goals,
                'count' => count($goals),
                'average_progress' => $totalGoalsProgress,
            ],
            'recent_transactions' => $recentTransactions,
        ];
    }

    private function getExpenseAnalysis(User $user, ?string $month = null): array
    {
        $date = $month ? Carbon::parse($this->parseDateParam($month)) : Carbon::now();
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
