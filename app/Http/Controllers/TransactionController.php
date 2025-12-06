<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Budget;
use App\Models\Goal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Account; // Added missing import for Account

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get month and year from request, default to current month
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);
        
        // Validate month and year
        $month = max(1, min(12, (int)$month));
        $year = max(2020, min(2030, (int)$year));
        
        // Get transactions for the specified month and year (for daily view)
        $transactions = Transaction::where('user_id', $user->id)
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->with(['category', 'account', 'transferAccount'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('transaction_time', 'desc')
            ->get();
        
        // Get transactions for multiple months (for monthly view)
        $monthlyTransactions = Transaction::where('user_id', $user->id)
            ->where('transaction_date', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->where('transaction_date', '<=', Carbon::now()->endOfMonth())
            ->with(['category', 'account', 'transferAccount'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('transaction_time', 'desc')
            ->get();
        
        // Group transactions by date
        $groupedTransactions = $transactions->groupBy('transaction_date');
        $groupedMonthlyTransactions = $monthlyTransactions->groupBy('transaction_date');
        
        // Get monthly summary
        $monthlySummary = $this->getMonthlySummary($user->id, $month, $year);
        
        // Get categories for this user
        $categories = Category::where('user_id', $user->id)->get();
        
        // Calculate previous and next month/year
        $prevMonth = $month == 1 ? 12 : $month - 1;
        $prevYear = $month == 1 ? $year - 1 : $year;
        $nextMonth = $month == 12 ? 1 : $month + 1;
        $nextYear = $month == 12 ? $year + 1 : $year;
        
        return view('transactions', compact(
            'transactions', 
            'groupedTransactions', 
            'groupedMonthlyTransactions',
            'monthlySummary', 
            'categories', 
            'month', 
            'year',
            'prevMonth',
            'prevYear',
            'nextMonth',
            'nextYear'
        ));
    }
    
    public function stats(Request $request)
    {
        $user = Auth::user();
        $period = $request->get('period', 'monthly');
        
        // Initialize variables for all periods
        $month = null;
        $year = null;
        $week = null;
        
        // Initialize query builder
        $query = Transaction::where('user_id', $user->id)
            ->with(['category', 'account']);
        
        // Apply date filters based on period
        if ($period === 'weekly') {
            $week = $request->get('week', Carbon::now()->week);
            $year = $request->get('year', Carbon::now()->year);
            
            // Validate week and year
            $week = max(1, min(53, (int)$week));
            $year = max(2020, min(2030, (int)$year));
            
            // Get start and end of week
            $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek();
            $endOfWeek = Carbon::now()->setISODate($year, $week)->endOfWeek();
            
            $query->whereBetween('transaction_date', [$startOfWeek, $endOfWeek]);
            
            $prevWeek = $week > 1 ? $week - 1 : 52;
            $prevYear = $week > 1 ? $year : $year - 1;
            $nextWeek = $week < 52 ? $week + 1 : 1;
            $nextYear = $week < 52 ? $year : $year + 1;
            
        } elseif ($period === 'annually') {
            $year = $request->get('year', Carbon::now()->year);
            
            // Validate year
            $year = max(2020, min(2030, (int)$year));
            
            $query->whereYear('transaction_date', $year);
            
            $prevYear = $year - 1;
            $nextYear = $year + 1;
            
        } else { // monthly (default)
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            
            // Validate month and year
            $month = max(1, min(12, (int)$month));
            $year = max(2020, min(2030, (int)$year));
            
            $query->whereYear('transaction_date', $year)
                  ->whereMonth('transaction_date', $month);
            
            $prevMonth = $month > 1 ? $month - 1 : 12;
            $prevYear = $month > 1 ? $year : $year - 1;
            $nextMonth = $month < 12 ? $month + 1 : 1;
            $nextYear = $month < 12 ? $year : $year + 1;
        }
        
        // Get all transactions
        $allTransactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('transaction_time', 'desc')
            ->get();
        
        // Get income transactions by category
        $incomeQuery = clone $query;
        $incomeData = $incomeQuery->where('type', 'income')
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(function ($transactions) {
                $category = $transactions->first()->category;
                return [
                    'category_name' => $category ? $category->name : 'No Category',
                    'amount' => $transactions->sum('amount'),
                    'count' => $transactions->count()
                ];
            })
            ->values();
        
        // Get expense transactions by category
        $expenseQuery = clone $query;
        $expenseData = $expenseQuery->where('type', 'expense')
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(function ($transactions) use ($user, $period, $month, $year, $week) {
                $category = $transactions->first()->category;
                $spentAmount = $transactions->sum('amount');
                
                // Get budget for this category
                $budget = null;
                if ($category) {
                    // Budgets are only stored monthly, so we need to get the budget for the period being viewed
                    $budgetMonth = $month ?? Carbon::now()->month;
                    $budgetYear = $year ?? Carbon::now()->year;
                    
                    $budget = Budget::where('user_id', $user->id)
                        ->where('category_id', $category->id)
                        ->where('month', $budgetMonth)
                        ->where('year', $budgetYear)
                        ->first();
                }
                
                // Calculate spent amount for this category in the budget month
                $budgetSpent = 0;
                if ($budget) {
                    $budgetSpent = Transaction::where('user_id', $user->id)
                        ->where('category_id', $category->id)
                        ->where('type', 'expense')
                        ->whereMonth('transaction_date', $budgetMonth)
                        ->whereYear('transaction_date', $budgetYear)
                        ->sum('amount');
                }
                
                return [
                    'category_id' => $category ? $category->id : null,
                    'category_name' => $category ? $category->name : 'No Category',
                    'amount' => $spentAmount,
                    'count' => $transactions->count(),
                    'budget' => $budget ? [
                        'id' => $budget->id,
                        'amount' => $budget->amount,
                        'spent' => $budgetSpent,
                        'remaining' => $budget->amount - $budgetSpent,
                        'percentage' => $budget->amount > 0 ? round(($budgetSpent / $budget->amount) * 100, 1) : 0,
                        'is_over_budget' => $budgetSpent > $budget->amount
                    ] : null
                ];
            })
            ->values();
        
        // Calculate totals
        $totalIncome = $incomeData->sum('amount');
        $totalExpense = $expenseData->sum('amount');
        $totalTransactions = $allTransactions->count();
        
        // Chart colors
        $colors = [
            '#EF4444', '#F97316', '#F59E0B', '#EAB308', '#84CC16', 
            '#22C55E', '#10B981', '#14B8A6', '#06B6D4', '#0EA5E9',
            '#3B82F6', '#6366F1', '#8B5CF6', '#A855F7', '#D946EF',
            '#EC4899', '#F43F5E'
        ];
        
        // Prepare navigation variables based on period
        $navigationData = [
            'allTransactions',
            'incomeData',
            'expenseData',
            'totalIncome',
            'totalExpense',
            'totalTransactions',
            'colors',
            'period'
        ];
        
        if ($period === 'weekly') {
            $navigationData = array_merge($navigationData, [
                'week', 'year', 'prevWeek', 'prevYear', 'nextWeek', 'nextYear'
            ]);
        } elseif ($period === 'annually') {
            $navigationData = array_merge($navigationData, [
                'year', 'prevYear', 'nextYear'
            ]);
        } else { // monthly
            $navigationData = array_merge($navigationData, [
                'month', 'year', 'prevMonth', 'prevYear', 'nextMonth', 'nextYear'
            ]);
        }
        
        return view('stats', compact($navigationData));
    }
    
    public function create()
    {
        $user = Auth::user();
        $categories = Category::where('user_id', $user->id)->get();
        $accounts = Account::where('user_id', $user->id)->where('is_active', true)->orderBy('is_default', 'desc')->orderBy('name')->get();
        
        return view('transaction-form', compact('categories', 'accounts'));
    }

    /**
     * Store a newly created transaction in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Validate request
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'transfer_account_id' => 'nullable|exists:accounts,id',
            'transaction_type' => 'required|in:income,expense,transfer',
        ]);

        try {
            // Use transaction type from form
            $transactionType = $request->transaction_type;
            
            // For transfer type, validate transfer account
            if ($transactionType === 'transfer' && !$request->transfer_account_id) {
                return back()->withInput()
                    ->with('error', 'Account tujuan wajib diisi untuk transaksi transfer.');
            }
            
            // For income/expense, validate category
            if (in_array($transactionType, ['income', 'expense']) && !$request->category_id) {
                return back()->withInput()
                    ->with('error', 'Kategori wajib diisi untuk transaksi ' . ($transactionType === 'income' ? 'pemasukan' : 'pengeluaran') . '.');
            }

            // Handle picture upload if provided
            $picturePath = null;
            if ($request->hasFile('picture')) {
                $picturePath = $this->uploadPicture($request->file('picture'), $user->id);
            }

            // Create transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'account_id' => $request->account_id,
                'transfer_account_id' => $request->transfer_account_id,
                'category_id' => $request->category_id,
                'type' => $transactionType,
                'amount' => $request->amount,
                'description' => $request->description,
                'notes' => $request->notes,
                'picture' => $picturePath,
                'transaction_date' => $request->date,
                'transaction_time' => $request->time ?? now()->format('H:i:s'),
            ]);

            // Update account balances
            $this->updateAccountBalances($transaction);

            return redirect()->route('transactions')
                ->with('success', 'Transaksi berhasil ditambahkan!');

        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal menambahkan transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Upload picture and return file path
     */
    private function uploadPicture($file, $userId): string
    {
        $fileName = 'transaction_' . $userId . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        
        // Store to public disk
        $filePath = $file->storeAs('transaction-pictures', $fileName, 'public');
        
        // Return the path relative to public storage
        return $filePath;
    }

    /**
     * Update account balances after transaction
     */
    private function updateAccountBalances(Transaction $transaction): void
    {
        if ($transaction->type === 'transfer') {
            // For transfers, decrease from source account, increase to destination account
            $sourceAccount = Account::find($transaction->account_id);
            $destAccount = Account::find($transaction->transfer_account_id);
            
            if ($sourceAccount && $destAccount) {
                $sourceAccount->decrement('balance', (float)$transaction->amount);
                $destAccount->increment('balance', (float)$transaction->amount);
            }
        } else {
            // For income/expense, update the account balance
            $account = Account::find($transaction->account_id);
            if ($account) {
                if ($transaction->type === 'income') {
                    $account->increment('balance', (float)$transaction->amount);
                } else {
                    $account->decrement('balance', (float)$transaction->amount);
                }
            }
        }
    }
    
    private function getMonthlySummary($userId, $month, $year)
    {
        $transactions = Transaction::where('user_id', $userId)
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->get();
        
        $income = $transactions->where('type', 'income')->sum('amount');
        $expenses = $transactions->where('type', 'expense')->sum('amount');
        $total = $income - $expenses;
        
        return [
            'income' => $income,
            'expenses' => $expenses,
            'total' => $total,
            'transaction_count' => $transactions->count()
        ];
    }
    
    public function getTransactionsByDate(Request $request)
    {
        $user = Auth::user();
        $date = $request->get('date');
        
        $transactions = Transaction::where('user_id', $user->id)
            ->where('transaction_date', $date)
            ->with('category')
            ->orderBy('transaction_time', 'desc')
            ->get();
        
        return response()->json($transactions);
    }
    
    public function getMonthlyStats(Request $request)
    {
        $user = Auth::user();
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);
        
        $summary = $this->getMonthlySummary($user->id, $month, $year);
        
        return response()->json($summary);
    }


    /**
     * Show the form for editing the specified transaction.
     */
    public function edit(Transaction $transaction)
    {
        $user = Auth::user();
        
        // Check if transaction belongs to user
        if ($transaction->user_id !== $user->id) {
            abort(403, 'Unauthorized access to transaction.');
        }
        
        // Get user's categories and accounts
        $categories = Category::where('user_id', $user->id)->get();
        $accounts = Account::where('user_id', $user->id)->where('is_active', true)->orderBy('is_default', 'desc')->orderBy('name')->get();
        
        return view('transactions.edit', compact('transaction', 'categories', 'accounts'));
    }

    /**
     * Update the specified transaction in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        $user = Auth::user();
        
        // Check if transaction belongs to user
        if ($transaction->user_id !== $user->id) {
            abort(403, 'Unauthorized access to transaction.');
        }
        
        // Validate request
        $request->validate([
            'type' => 'required|in:income,expense,transfer',
            'amount' => 'required|numeric|min:0',
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'transaction_date' => 'required|date',
            'transaction_time' => 'nullable|date_format:H:i',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'transfer_account_id' => 'nullable|exists:accounts,id',
        ]);

        try {
            // For transfer type, validate transfer account
            if ($request->type === 'transfer' && !$request->transfer_account_id) {
                return back()->withInput()
                    ->with('error', 'Account tujuan wajib diisi untuk transaksi transfer.');
            }
            
            // For income/expense, validate category
            if (in_array($request->type, ['income', 'expense']) && !$request->category_id) {
                return back()->withInput()
                    ->with('error', 'Kategori wajib diisi untuk transaksi ' . ($request->type === 'income' ? 'pemasukan' : 'pengeluaran') . '.');
            }

            // Store old values for balance recalculation
            $oldTransaction = $transaction->replicate();
            
            // Handle picture upload if provided
            $picturePath = $transaction->picture; // Keep existing picture
            if ($request->hasFile('picture')) {
                // Delete old picture if exists
                if ($transaction->picture && Storage::disk('public')->exists($transaction->picture)) {
                    Storage::disk('public')->delete($transaction->picture);
                }
                $picturePath = $this->uploadPicture($request->file('picture'), $user->id);
            }

            // Update transaction
            $transaction->update([
                'account_id' => $request->account_id,
                'transfer_account_id' => $request->type === 'transfer' ? $request->transfer_account_id : null,
                'category_id' => $request->type === 'transfer' ? null : $request->category_id,
                'type' => $request->type,
                'amount' => $request->amount,
                'description' => $request->description,
                'notes' => $request->notes,
                'picture' => $picturePath,
                'transaction_date' => $request->transaction_date,
                'transaction_time' => $request->transaction_time ? $request->transaction_time . ':00' : null,
            ]);

            // Recalculate account balances
            $this->recalculateAccountBalances($oldTransaction, $transaction);

            return redirect()->route('transactions')
                ->with('success', 'Transaction updated successfully!');

        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal memperbarui transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified transaction from storage.
     */
    public function destroy(Transaction $transaction)
    {
        $user = Auth::user();
        
        // Check if transaction belongs to user
        if ($transaction->user_id !== $user->id) {
            abort(403, 'Unauthorized access to transaction.');
        }

        try {
            // Store transaction data for balance recalculation
            $oldTransaction = $transaction->replicate();
            
            // Delete picture if exists
            if ($transaction->picture && Storage::disk('public')->exists($transaction->picture)) {
                Storage::disk('public')->delete($transaction->picture);
            }
            
            // Delete transaction
            $transaction->delete();
            
            // Recalculate account balances (reverse the transaction)
            $this->reverseAccountBalances($oldTransaction);

            return redirect()->route('transactions')
                ->with('success', 'Transaksi berhasil dihapus!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Recalculate account balances when transaction is updated
     */
    private function recalculateAccountBalances(Transaction $oldTransaction, Transaction $newTransaction): void
    {
        // First, reverse the old transaction
        $this->reverseAccountBalances($oldTransaction);
        
        // Then apply the new transaction
        $this->updateAccountBalances($newTransaction);
    }

    /**
     * Reverse account balances (for deletion or update)
     */
    private function reverseAccountBalances(Transaction $transaction): void
    {
        if ($transaction->type === 'transfer') {
            // For transfers, reverse: increase source account, decrease destination account
            $sourceAccount = Account::find($transaction->account_id);
            $destAccount = Account::find($transaction->transfer_account_id);
            
            if ($sourceAccount && $destAccount) {
                $sourceAccount->increment('balance', (float)$transaction->amount);
                $destAccount->decrement('balance', (float)$transaction->amount);
            }
        } else {
            // For income/expense, reverse the account balance
            $account = Account::find($transaction->account_id);
            if ($account) {
                if ($transaction->type === 'income') {
                    $account->decrement('balance', (float)$transaction->amount);
                } else {
                    $account->increment('balance', (float)$transaction->amount);
                }
            }
        }
    }
}
