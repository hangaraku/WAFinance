<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BudgetController extends Controller
{
    /**
     * Display a listing of the budgets.
     */
    public function index()
    {
        $user = Auth::user();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        // Get budgets for current month
        $budgets = Budget::where('user_id', $user->id)
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->with('category')
            ->get();
            
        // Get categories for creating new budgets
        $categories = Category::where('user_id', $user->id)
            ->whereIn('type', ['expense'])
            ->get();
            
        // Calculate total budget and spent
        $totalBudget = $budgets->sum('amount');
        $totalSpent = $this->calculateTotalSpent($user->id, $currentMonth, $currentYear);
        $remainingBudget = $totalBudget - $totalSpent;
        
        // Get monthly summary for context
        $monthlySummary = $this->getMonthlySummary($user->id, $currentMonth, $currentYear);
        
        return view('budgets.index', compact(
            'budgets', 
            'categories', 
            'totalBudget', 
            'totalSpent', 
            'remainingBudget',
            'monthlySummary',
            'currentMonth',
            'currentYear'
        ));
    }

    /**
     * Show the form for creating a new budget.
     */
    public function create()
    {
        $user = Auth::user();
        $categories = Category::where('user_id', $user->id)
            ->whereIn('type', ['expense'])
            ->get();
            
        return view('budgets.create', compact('categories'));
    }

    /**
     * Store a newly created budget in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020|max:2030',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        
        // Check if budget already exists for this category and month
        $existingBudget = Budget::where('user_id', $user->id)
            ->where('category_id', $request->category_id)
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->first();
            
        if ($existingBudget) {
            return back()->withInput()
                ->with('error', 'Budget untuk kategori ini sudah ada di bulan yang dipilih.');
        }

        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $request->category_id,
            'amount' => $request->amount,
            'month' => $request->month,
            'year' => $request->year,
            'notes' => $request->notes,
        ]);

        return redirect()->route('budgets.index')
            ->with('success', 'Budget berhasil dibuat!');
    }

    /**
     * Show the form for editing the specified budget.
     */
    public function edit(Budget $budget)
    {
        // Ensure user can only edit their own budgets
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

        $user = Auth::user();
        $categories = Category::where('user_id', $user->id)
            ->whereIn('type', ['expense'])
            ->get();
            
        return view('budgets.edit', compact('budget', 'categories'));
    }

    /**
     * Update the specified budget in storage.
     */
    public function update(Request $request, Budget $budget)
    {
        // Ensure user can only update their own budgets
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020|max:2030',
            'notes' => 'nullable|string|max:1000',
        ]);

        $budget->update($request->only(['category_id', 'amount', 'month', 'year', 'notes']));

        return redirect()->route('budgets.index')
            ->with('success', 'Budget berhasil diupdate!');
    }

    /**
     * Remove the specified budget from storage.
     */
    public function destroy(Budget $budget)
    {
        // Ensure user can only delete their own budgets
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

        $budget->delete();

        return redirect()->route('budgets.index')
            ->with('success', 'Budget berhasil dihapus!');
    }

    /**
     * Calculate total spent for current month
     */
    private function calculateTotalSpent($userId, $month, $year): float
    {
        return Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->sum('amount');
    }

    /**
     * Get monthly summary for context
     */
    private function getMonthlySummary($userId, $month, $year): array
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
}
